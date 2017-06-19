<?php

namespace AppBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;

/**
 * @Route("/{product}")
 */
class ReportController extends Controller
{
    /**
     * @Route("/{version}")
     */
    public function reportAction($product, $version)
    {
        $data = $this->getCucumberJson($product, $version);
        $images = $this->getDirImages($product, $version);
        $resultSummary = $this->getSummary($data);
        $senarioSummary = $resultSummary['senario_summary'];
        $countTotal = $resultSummary['count_total'];
        $featureSummary = $this->getFeatureSummary($senarioSummary);

        return $this->render('AppBundle:Report:index.html.twig', [
            'product' => $product,
            'version' => $version,
            'result_data' => $data,
            'count_total' => $countTotal,
            'feature_summary_total'=> $featureSummary,
            'senario_summary_total' => $senarioSummary,
            'images' => $images,
        ]);
    }

    private function getSummary($data)
    {
        $arrayFeatureFile = [];
        $countTotalFeatures = count($data);
        $countTotalSenarios = 0;
        $countTotalSteps = 0;

        $countTotalPassed = 0;
        $countTotalFailed = 0;
        $countTotalPending = 0;
        $countTotalSkipped = 0;

        $senarioSummary = [];

        foreach ($data as $features) {
            $countTotalSenarios += count($features['elements']);
            $summaryScenarios = [];

            foreach ($features['elements'] as $scenarios) {
                $countTotalSteps += count($scenarios['steps']);
                $summaryTotalPassed = 0;
                $summaryTotalFailed = 0;
                $summaryTotalPending = 0;
                $summaryTotalSkipped = 0;

                foreach ($scenarios['steps'] as $steps) {

                    switch ($steps['result']['status']) {
                        case 'passed':
                            $countTotalPassed += 1;
                            $summaryTotalPassed += 1;
                            break;
                        case 'failed':
                            $countTotalFailed += 1;
                            $summaryTotalFailed += 1;
                            break;
                        case 'pending':
                            $countTotalPending += 1;
                            $summaryTotalPending += 1;
                            break;
                        case 'skipped':
                            $countTotalSkipped += 1;
                            $summaryTotalSkipped += 1;
                            break;
                        
                        default:
                            break;
                    }
                    $summaryScenarios = array_merge($summaryScenarios, array($scenarios['name'] => [
                        'passed' => $summaryTotalPassed,
                        'failed' => $summaryTotalFailed,
                        'pending' => $summaryTotalPending,
                        'skipped' => $summaryTotalSkipped,
                        ])
                    );
                }
            }
            $senarioSummary = array_merge($senarioSummary, array($features['name'] => $summaryScenarios));
        }

        $countTotal = [
            'total_features' => $countTotalFeatures,
            'total_senarios' => $countTotalSenarios, 
            'total_steps' => $countTotalSteps,
            'total_passed' => $countTotalPassed,
            'total_failed' => $countTotalFailed,
            'total_pending' => $countTotalPending,
            'total_skipped' => $countTotalSkipped,
            'percent_passed' => round(($countTotalPassed / $countTotalSteps) * 100),
            'percent_failed' => round(($countTotalFailed / $countTotalSteps) * 100),
            'percent_pending' => round(($countTotalPending / $countTotalSteps) * 100),
            'percent_skipped' => round(($countTotalSkipped / $countTotalSteps) * 100),
        ];

        $result = ['senario_summary' => $senarioSummary, 'count_total' => $countTotal];
        return $result;
    }

    private function getFeatureSummary($senarioSummary)
    {
        $featureSummary = [];
        foreach ($senarioSummary as $key => $value) {

            $featureSummaryArray = [];
            $featureSummaryPassed = 0;
            $featureSummaryFailed = 0;
            $featureSummaryPending = 0;
            $featureSummarySkipped = 0;

            foreach ($value as $value2) {
                $featureSummaryPassed += $value2['passed'];
                $featureSummaryFailed += $value2['failed'];
                $featureSummaryPending += $value2['pending'];
                $featureSummarySkipped += $value2['skipped'];
            }

            $featureSummaryArray = [
                'passed' => $featureSummaryPassed,
                'failed' => $featureSummaryFailed,
                'pending' => $featureSummaryPending,
                'skipped' => $featureSummarySkipped,
            ];

            $featureSummary = array_merge($featureSummary, array($key => $featureSummaryArray));
        }
        return $featureSummary;
    }

    private function getCucumberJson($productName, $version)
    {
        $filePath = '../src/AppBundle/Resources/config/' . $productName . '/' . $version . '/result.json';
        $json = file_get_contents($filePath);
        if ($json === false) {
            throw new \RuntimeException('file not found.');
        }
        $data = json_decode($json, true);
        
        return $data;
    }

    private function getDirImages($productName, $version)
    {
        $filePath = '../web/image/' . $productName . '/' . $version;

        $images = [];
        $files = scandir($filePath);
        foreach ($files as $value) {
            if(strpos($value, '.png') !== false){
                array_push($images, $value);
            }
        }
        
        sort($images);

        return $images;

    }
}