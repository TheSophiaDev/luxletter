<?php

declare(strict_types=1);
namespace In2code\Luxletter\Widget\DataProvider;

use Doctrine\DBAL\DBALException;
use In2code\Luxletter\Domain\Repository\LogRepository;
use In2code\Luxletter\Utility\LocalizationUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Dashboard\WidgetApi;
use TYPO3\CMS\Dashboard\Widgets\ChartDataProviderInterface;

/**
 * Class UnsubscribeRateDataProvider
 * @noinspection PhpUnused
 */
class UnsubscribeRateDataProvider implements ChartDataProviderInterface
{
    /**
     * @return array
     * @throws DBALException
     */
    public function getChartData(): array
    {
        return [
            'labels' => $this->getData()['titles'],
            'datasets' => [
                [
                    'label' => $this->getWidgetLabel('unsubscriberate.label'),
                    'backgroundColor' => [
                        WidgetApi::getDefaultChartColors()[0],
                        '#dddddd',
                    ],
                    'border' => 0,
                    'data' => $this->getData()['amounts'],
                ],
            ],
        ];
    }

    /**
     *  [
     *      'amounts' => [
     *          200,
     *          66
     *      ],
     *      'titles' => [
     *          'Label',
     *          'Label 2'
     *      ]
     *  ]
     *
     * @return array
     * @throws DBALException
     */
    protected function getData(): array
    {
        $logRepository = GeneralUtility::makeInstance(LogRepository::class);
        return [
            'amounts' => [
                $logRepository->getOverallUnsubscribes(),
                ($logRepository->getOverallOpenings() - $logRepository->getOverallUnsubscribes()),
            ],
            'titles' => [
                $this->getWidgetLabel('unsubscriberate.label.0'),
                $this->getWidgetLabel('unsubscriberate.label.1'),
            ],
        ];
    }

    /**
     * @param string $key e.g. "browser.label"
     * @return string
     */
    protected function getWidgetLabel(string $key): string
    {
        $label = LocalizationUtility::translate(
            'LLL:EXT:luxletter/Resources/Private/Language/locallang_db.xlf:module.dashboard.widget.' . $key
        );
        if (empty($label)) {
            $label = $key;
        }
        return $label;
    }
}
