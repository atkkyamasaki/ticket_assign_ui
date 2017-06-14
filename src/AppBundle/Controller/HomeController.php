<?php

namespace AppBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;


class HomeController extends Controller
{
    /**
     * @Route("/")
     */
    public function indexAction()
    {
        return $this->render('AppBundle:Home:index.html.twig');
    }

    /**
     * @Route("/vista_manager/{version}")
     */
    public function vistaManagerAction($version)
    {
        $data = $this->getCucumberJson('vista_manager', $version);
        $images = $this->getDirImages('vista_manager', $version);

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

        return $this->render('AppBundle:Home:vista_manager.html.twig', [
            'result_data' => $data,
            'count_total' => $countTotal,
            'feature_summary_total'=> $featureSummary,
            'senario_summary_total' => $senarioSummary,
            'images' => $images,
        ]);
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
        $filePath = '../src/AppBundle/Resources/config/' . $productName . '/' . $version . '/image';

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


