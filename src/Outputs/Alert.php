<?php

declare(strict_types=1);

namespace BlissJaspis\QueryDetector\Outputs;

use BlissJaspis\QueryDetector\Contracts\Output;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Symfony\Component\HttpFoundation\Response;

class Alert implements Output
{
    public function boot(): void
    {
        //
    }

    public function output(Collection $detectedQueries, Response $response, ?Request $request = null): void
    {
        if (stripos($response->headers->get('Content-Type'), 'text/html') !== 0 || $response->isRedirection()) {
            return;
        }

        $content = $response->getContent();

        $outputContent = $this->getOutputContent($detectedQueries);

        $pos = strripos($content, '</body>');

        if ($pos !== false) {
            $content = substr($content, 0, $pos).$outputContent.substr($content, $pos);
        } else {
            $content = $content.$outputContent;
        }

        $response->setContent($content);

        $response->headers->remove('Content-Length');
    }

    protected function getOutputContent(Collection $detectedQueries): string
    {
        $output = '<script type="text/javascript">';
        $output .= "alert('Found the following N+1 queries in this request:\\n\\n";
        foreach ($detectedQueries as $detectedQuery) {
            $output .= 'Model: '.addslashes($detectedQuery['model']).' => Relation: '.addslashes($detectedQuery['relation']);
            $output .= " - You should add \"with(\'".addslashes($detectedQuery['relation'])."\')\" to eager-load this relation.";
            $output .= '\\n';
        }
        $output .= "')";
        $output .= '</script>';

        return $output;
    }
}
