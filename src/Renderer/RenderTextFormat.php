<?php
declare(strict_types=1);

namespace ErickSkrauch\Prometheus\Renderer;

final class RenderTextFormat implements RendererInterface {

    public const MIME_TYPE = 'text/plain; version=0.0.4';

    public function render(array $metrics): string {
        $result = '';
        /** @var \ErickSkrauch\Prometheus\Metric\MetricFamilySamples $metric */
        foreach ($metrics as $metric) {
            if ($metric->help !== '') {
                $result .= "# HELP {$metric->name} {$metric->help}\n";
            }

            $result .= "# TYPE {$metric->name} {$metric->type}\n";
            foreach ($metric->samples as $sample) {
                $labelsStr = '';
                if ($sample->labels !== []) {
                    $labelsStr = '{' . self::serializeLabels($sample->labels) . '}';
                }

                $value = $sample->value;
                if (is_float($value) && is_infinite($value)) {
                    $value = $value > 0 ? '+Inf' : '-Inf';
                }

                $result .= "{$sample->name}{$labelsStr} {$value}\n";
            }

            $result .= "\n";
        }

        return $result;
    }

    /**
     * @param array<string, string> $labels
     */
    private static function serializeLabels(array $labels): string {
        $labelsStrs = [];
        foreach ($labels as $name => $value) {
            $value = self::escapeLabelValue($value);
            $labelsStrs[] = "{$name}=\"{$value}\"";
        }

        return implode(',', $labelsStrs);
    }

    private static function escapeLabelValue(string $v): string {
        return str_replace(['\\', "\n", '"'], ['\\\\', '\\n', '\\"'], $v);
    }

}
