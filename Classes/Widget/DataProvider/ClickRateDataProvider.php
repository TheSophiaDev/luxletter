<?php
declare(strict_types=1);
namespace In2code\Luxletter\Widget\DataProvider;

use Doctrine\DBAL\DBALException;
use In2code\Lux\Utility\LocalizationUtility;
use In2code\Lux\Utility\ObjectUtility;
use In2code\Luxletter\Domain\Repository\LogRepository;
use TYPO3\CMS\Dashboard\WidgetApi;
use TYPO3\CMS\Dashboard\Widgets\ChartDataProviderInterface;
use TYPO3\CMS\Extbase\Object\Exception;

/**
 * Class ClickRateDataProvider
 * @noinspection PhpUnused
 */
class ClickRateDataProvider implements ChartDataProviderInterface
{
    /**
     * @return array
     * @throws DBALException
     * @throws Exception
     */
    public function getChartData(): array
    {
        return [
            'labels' => $this->getData()['titles'],
            'datasets' => [
                [
                    'label' => $this->getWidgetLabel('clickrate.label'),
                    'backgroundColor' => [
                        WidgetApi::getDefaultChartColors()[0],
                        '#dddddd'
                    ],
                    'border' => 0,
                    'data' => $this->getData()['amounts']
                ]
            ]
        ];
    }

    /**
     *  [
     *      'amounts' => [
     *          200,
     *          66
     *      ],
     *      'titles' => [
     *          'Clicker',
     *          'NonClicker'
     *      ]
     *  ]
     *
     * @return array
     * @throws Exception
     * @throws DBALException
     */
    protected function getData(): array
    {
        $logRepository = ObjectUtility::getObjectManager()->get(LogRepository::class);
        return [
            'amounts' => [
                $logRepository->getOpeningsByClickers(),
                ($logRepository->getOverallOpenings() - $logRepository->getOpeningsByClickers())
            ],
            'titles' => [
                $this->getWidgetLabel('clickrate.label.0'),
                $this->getWidgetLabel('clickrate.label.1')
            ]
        ];
    }

    /**
     * @param string $key e.g. "browser.label"
     * @return string
     */
    protected function getWidgetLabel(string $key): string
    {
        $label = LocalizationUtility::getLanguageService()->sL(
            'LLL:EXT:luxletter/Resources/Private/Language/locallang_db.xlf:module.dashboard.widget.' . $key
        );
        if (empty($label)) {
            $label = $key;
        }
        return $label;
    }
}
