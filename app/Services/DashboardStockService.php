<?php

namespace App\Services;

use App\Repositories\DashboardStockRepository;
use App\Support\ApiResponse;

class DashboardStockService
{
    public function __construct(
        private DashboardStockRepository $dashboardStockRepository = new DashboardStockRepository()
    ) {
    }

    public function general(): array
    {
        $productCounts = $this->dashboardStockRepository->generalProductCounts();
        $inventoryStatusCounts = $this->dashboardStockRepository->inventoryStatusCounts();
        $stockMovementCounts = $this->dashboardStockRepository->stockMovementCounts();
        $purchaseLotStatusCounts = $this->dashboardStockRepository->purchaseLotStatusCounts();
        $totalInventoryValue = $this->dashboardStockRepository->totalInventoryValue();

        $data = array_merge(
            [
                'total_inventory_value' => $totalInventoryValue,
                'currency' => 'MAD',
            ],
            $productCounts,
            $inventoryStatusCounts,
            $stockMovementCounts,
            $purchaseLotStatusCounts
        );

        $data['stock_health_summary'] = $this->resolveStockHealthSummary(
            $data['low_stock_count'],
            $data['out_of_stock_count']
        );

        return ApiResponse::success(
            'General stock dashboard fetched successfully',
            $data
        );
    }

    private function resolveStockHealthSummary(int $lowStockCount, int $outOfStockCount): string
    {
        if ($outOfStockCount > 0) {
            return 'CRITICAL';
        }

        if ($lowStockCount > 0) {
            return 'WARNING';
        }

        return 'HEALTHY';
    }

   public function productsPerformance(int $periodDays = 360): array
    {
        if ($periodDays <= 0) {
            $periodDays = 360;
        }

        $rows = $this->dashboardStockRepository->productsPerformance();

        $products = [];

        foreach ($rows as $row) {
            $currentStock = (float) $row['current_stock'];
            $stockInQuantity = (float) $row['stock_in_quantity'];
            $stockOutQuantity = (float) $row['stock_out_quantity'];
            $unitCost = (float) $row['latest_unit_purchase_cost'];

            /*
            * Estimated opening stock:
            * current_stock = opening_stock + stock_in - stock_out
            * therefore:
            * opening_stock = current_stock - stock_in + stock_out
            */
            $openingStock = $currentStock - $stockInQuantity + $stockOutQuantity;

            /*
            * Estimated average stock over the period.
            */
            $averageStock = ($openingStock + $currentStock) / 2;

            if ($averageStock < 0) {
                $averageStock = 0;
            }

            $stockRotationRate = $this->calculateStockRotationRate(
                $stockOutQuantity,
                $averageStock
            );

            $averageDailyConsumption = $this->calculateAverageDailyConsumption(
                $stockOutQuantity,
                $periodDays
            );

            $stockCoverageDays = $this->calculateStockCoverageDays(
                $currentStock,
                $averageDailyConsumption
            );

            $averageStorageDurationDays = $this->calculateAverageStorageDurationDays(
                $periodDays,
                $stockRotationRate
            );

            $products[] = [
                'id' => $row['id'],
                'name' => $row['name'],
                'sku' => $row['sku'],
                'category' => $row['category'],

                'current_stock' => round($currentStock, 4),
                'opening_stock' => round($openingStock, 4),
                'average_stock' => round($averageStock, 4),

                'low_stock_threshold' => $row['low_stock_threshold'],
                'stock_status' => $this->resolveStockStatus(
                    $currentStock,
                    (float) $row['low_stock_threshold']
                ),

                'latest_unit_purchase_cost' => round($unitCost, 4),
                'stock_value' => round($currentStock * $unitCost, 2),

                'stock_in_quantity' => round($stockInQuantity, 4),
                'stock_out_quantity' => round($stockOutQuantity, 4),
                'movement_count' => $row['movement_count'],
                'last_movement_at' => $row['last_movement_at'],

                'stock_rotation_rate' => $stockRotationRate,
                'average_daily_consumption' => $averageDailyConsumption,
                'stock_coverage_days' => $stockCoverageDays,
                'average_storage_duration_days' => $averageStorageDurationDays,

                /*
                * For this school-project version, stock_flow_time_days is treated
                * as equivalent to average_storage_duration_days.
                */
                'stock_flow_time_days' => $averageStorageDurationDays,
            ];
        }

        return ApiResponse::success(
            'Product stock performance fetched successfully',
            [
                'period_days' => $periodDays,
                'calculation_mode' => 'estimated_from_stock_movements',
                'products' => $products,
            ]
        );
    } 

    private function resolveStockStatus(float $currentStock, float $threshold): string
    {
        if ($currentStock <= 0) {
            return 'OUT_OF_STOCK';
        }

        if ($currentStock <= $threshold) {
            return 'LOW_STOCK';
        }

        return 'OK';
    }

    private function calculateStockRotationRate(float $stockOutQuantity, float $averageStock): ?float
    {
        if ($averageStock <= 0) {
            return null;
        }

        return round($stockOutQuantity / $averageStock, 4);
    }

    private function calculateAverageDailyConsumption(float $stockOutQuantity, int $periodDays): ?float
    {
        if ($periodDays <= 0) {
            return null;
        }

        return round($stockOutQuantity / $periodDays, 4);
    }

    private function calculateStockCoverageDays(float $currentStock, ?float $averageDailyConsumption): ?float
    {
        if ($averageDailyConsumption === null || $averageDailyConsumption <= 0) {
            return null;
        }

        return round($currentStock / $averageDailyConsumption, 2);
    }

    private function calculateAverageStorageDurationDays(int $periodDays, ?float $stockRotationRate): ?float
    {
        if ($stockRotationRate === null || $stockRotationRate <= 0) {
            return null;
        }

        return round($periodDays / $stockRotationRate, 2);
    }

    public function productDashboard(int $productId, int $periodDays = 360): array
    {
        if ($periodDays <= 0) {
            $periodDays = 360;
        }

        $row = $this->dashboardStockRepository->productPerformanceById($productId);

        if (!$row) {
            return ApiResponse::success(
                'Product stock dashboard fetched successfully',
                [
                    'period_days' => $periodDays,
                    'calculation_mode' => 'estimated_from_stock_movements',
                    'product' => null,
                    'kpis' => null,
                    'charts' => [
                        'stock_movements_by_type' => [],
                        'stock_movement_timeline' => [],
                        'cost_history' => [],
                    ],
                    'recent_activity' => null,
                ]
            );
        }

        $currentStock = (float) $row['current_stock'];
        $stockInQuantity = (float) $row['stock_in_quantity'];
        $stockOutQuantity = (float) $row['stock_out_quantity'];
        $unitCost = (float) $row['latest_unit_purchase_cost'];

        /*
        * current_stock = opening_stock + stock_in - stock_out
        * therefore:
        * opening_stock = current_stock - stock_in + stock_out
        */
        $openingStock = $currentStock - $stockInQuantity + $stockOutQuantity;

        $averageStock = ($openingStock + $currentStock) / 2;

        if ($averageStock < 0) {
            $averageStock = 0;
        }

        $stockRotationRate = $this->calculateStockRotationRate(
            $stockOutQuantity,
            $averageStock
        );

        $averageDailyConsumption = $this->calculateAverageDailyConsumption(
            $stockOutQuantity,
            $periodDays
        );

        $stockCoverageDays = $this->calculateStockCoverageDays(
            $currentStock,
            $averageDailyConsumption
        );

        $averageStorageDurationDays = $this->calculateAverageStorageDurationDays(
            $periodDays,
            $stockRotationRate
        );

        $movementTimeline = $this->dashboardStockRepository
            ->productMovementTimeline($productId);

        $costHistory = $this->dashboardStockRepository
            ->productCostHistory($productId);

        return ApiResponse::success(
            'Product stock dashboard fetched successfully',
            [
                'period_days' => $periodDays,
                'calculation_mode' => 'estimated_from_stock_movements',

                'product' => [
                    'id' => $row['id'],
                    'name' => $row['name'],
                    'sku' => $row['sku'],
                    'category' => $row['category'],
                ],

                'kpis' => [
                    'current_stock' => round($currentStock, 4),
                    'opening_stock' => round($openingStock, 4),
                    'average_stock' => round($averageStock, 4),

                    'low_stock_threshold' => $row['low_stock_threshold'],
                    'stock_status' => $this->resolveStockStatus(
                        $currentStock,
                        (float) $row['low_stock_threshold']
                    ),

                    'latest_unit_purchase_cost' => round($unitCost, 4),
                    'stock_value' => round($currentStock * $unitCost, 2),

                    'stock_in_quantity' => round($stockInQuantity, 4),
                    'stock_out_quantity' => round($stockOutQuantity, 4),

                    'stock_rotation_rate' => $stockRotationRate,
                    'average_daily_consumption' => $averageDailyConsumption,
                    'stock_coverage_days' => $stockCoverageDays,
                    'average_storage_duration_days' => $averageStorageDurationDays,
                    'stock_flow_time_days' => $averageStorageDurationDays,

                    'has_consumption_data' => $stockOutQuantity > 0,
                ],

                'charts' => [
                    'stock_movements_by_type' => [
                        [
                            'label' => 'Entrées',
                            'type' => 'in',
                            'value' => round($stockInQuantity, 4),
                        ],
                        [
                            'label' => 'Sorties',
                            'type' => 'out',
                            'value' => round($stockOutQuantity, 4),
                        ],
                    ],

                    'stock_movement_timeline' => $movementTimeline,

                    'cost_history' => array_map(function (array $lot) {
                        return [
                            'id' => $lot['id'],
                            'date' => $lot['purchase_date'],
                            'status' => $lot['status'],
                            'quantity_received' => $lot['quantity_received'],
                            'unit_purchase_cost' => $lot['unit_purchase_cost'],
                            'total_purchase_cost' => $lot['total_purchase_cost'],
                        ];
                    }, $costHistory),
                ],

                'recent_activity' => [
                    'last_movement_at' => $row['last_movement_at'],
                    'movement_count' => $row['movement_count'],
                    'purchase_lot_count' => count($costHistory),
                ],
            ]
        );
    }

    public function abc(): array
    {
        $rows = $this->dashboardStockRepository->abcBaseProducts();

        $products = [];

        foreach ($rows as $row) {
            $currentStock = (float) $row['current_stock'];
            $unitCost = (float) $row['latest_unit_purchase_cost'];
            $stockValue = round($currentStock * $unitCost, 2);

            $products[] = [
                'id' => $row['id'],
                'name' => $row['name'],
                'sku' => $row['sku'],
                'category' => $row['category'],

                'current_stock' => round($currentStock, 4),
                'low_stock_threshold' => $row['low_stock_threshold'],
                'stock_status' => $this->resolveStockStatus(
                    $currentStock,
                    (float) $row['low_stock_threshold']
                ),

                'latest_unit_purchase_cost' => round($unitCost, 4),
                'stock_value' => $stockValue,

                'stock_in_quantity' => round((float) $row['stock_in_quantity'], 4),
                'stock_out_quantity' => round((float) $row['stock_out_quantity'], 4),
                'movement_count' => $row['movement_count'],
                'last_movement_at' => $row['last_movement_at'],
            ];
        }

        usort($products, function (array $a, array $b) {
            return $b['stock_value'] <=> $a['stock_value'];
        });

        $totalInventoryValue = array_sum(array_column($products, 'stock_value'));
        $cumulativeValue = 0.0;

        foreach ($products as &$product) {
            $valueSharePercent = $totalInventoryValue > 0
                ? ($product['stock_value'] / $totalInventoryValue) * 100
                : 0;

            $cumulativeValue += $valueSharePercent;

            $product['value_share_percent'] = round($valueSharePercent, 2);
            $product['cumulative_value_percent'] = round($cumulativeValue, 2);
            $product['abc_class'] = $this->resolveAbcClass($cumulativeValue);
        }

        unset($product);

        $classes = $this->buildAbcClassSummaries($products, $totalInventoryValue);

        $giniCoefficient = $this->calculateGiniCoefficient(
            array_column($products, 'stock_value')
        );

        return ApiResponse::success(
            'ABC stock dashboard fetched successfully',
            [
                'global' => [
                    'total_products' => count($products),
                    'total_inventory_value' => round($totalInventoryValue, 2),
                    'currency' => 'MAD',
                    'gini_coefficient' => $giniCoefficient,
                    'gini_label' => $this->resolveGiniLabel($giniCoefficient),

                    'a_value_share_percent' => $classes['A']['value_share_percent'],
                    'b_value_share_percent' => $classes['B']['value_share_percent'],
                    'c_value_share_percent' => $classes['C']['value_share_percent'],
                ],

                'charts' => [
                    'value_by_class' => [
                        [
                            'class' => 'A',
                            'label' => 'Classe A',
                            'value' => $classes['A']['inventory_value'],
                            'value_share_percent' => $classes['A']['value_share_percent'],
                        ],
                        [
                            'class' => 'B',
                            'label' => 'Classe B',
                            'value' => $classes['B']['inventory_value'],
                            'value_share_percent' => $classes['B']['value_share_percent'],
                        ],
                        [
                            'class' => 'C',
                            'label' => 'Classe C',
                            'value' => $classes['C']['inventory_value'],
                            'value_share_percent' => $classes['C']['value_share_percent'],
                        ],
                    ],

                    'product_count_by_class' => [
                        [
                            'class' => 'A',
                            'label' => 'Classe A',
                            'value' => $classes['A']['product_count'],
                        ],
                        [
                            'class' => 'B',
                            'label' => 'Classe B',
                            'value' => $classes['B']['product_count'],
                        ],
                        [
                            'class' => 'C',
                            'label' => 'Classe C',
                            'value' => $classes['C']['product_count'],
                        ],
                    ],

                    'risk_by_class' => [
                        [
                            'class' => 'A',
                            'label' => 'Classe A',
                            'low_stock_count' => $classes['A']['low_stock_count'],
                            'out_of_stock_count' => $classes['A']['out_of_stock_count'],
                        ],
                        [
                            'class' => 'B',
                            'label' => 'Classe B',
                            'low_stock_count' => $classes['B']['low_stock_count'],
                            'out_of_stock_count' => $classes['B']['out_of_stock_count'],
                        ],
                        [
                            'class' => 'C',
                            'label' => 'Classe C',
                            'low_stock_count' => $classes['C']['low_stock_count'],
                            'out_of_stock_count' => $classes['C']['out_of_stock_count'],
                        ],
                    ],
                ],

                'classes' => $classes,
            ]
        );
    }

    private function resolveAbcClass(float $cumulativeValuePercent): string
    {
        if ($cumulativeValuePercent <= 80) {
            return 'A';
        }

        if ($cumulativeValuePercent <= 95) {
            return 'B';
        }

        return 'C';
    }

    private function buildAbcClassSummaries(array $products, float $totalInventoryValue): array
    {
        $classes = [
            'A' => $this->emptyAbcClass('A', 'Classe A', 'Articles critiques à forte valeur'),
            'B' => $this->emptyAbcClass('B', 'Classe B', 'Articles à valeur intermédiaire'),
            'C' => $this->emptyAbcClass('C', 'Classe C', 'Articles à faible valeur individuelle'),
        ];

        foreach ($products as $product) {
            $class = $product['abc_class'];

            $classes[$class]['products'][] = $product;
            $classes[$class]['product_count']++;
            $classes[$class]['inventory_value'] += $product['stock_value'];

            if ($product['stock_status'] === 'LOW_STOCK') {
                $classes[$class]['low_stock_count']++;
            }

            if ($product['stock_status'] === 'OUT_OF_STOCK') {
                $classes[$class]['out_of_stock_count']++;
            }
        }

        foreach ($classes as &$classData) {
            $stocks = array_column($classData['products'], 'current_stock');

            $classData['inventory_value'] = round($classData['inventory_value'], 2);

            $classData['value_share_percent'] = $totalInventoryValue > 0
                ? round(($classData['inventory_value'] / $totalInventoryValue) * 100, 2)
                : 0;

            $classData['avg_current_stock'] = count($stocks) > 0
                ? round(array_sum($stocks) / count($stocks), 4)
                : 0;

            $classData['min_current_stock'] = count($stocks) > 0
                ? min($stocks)
                : 0;

            $classData['max_current_stock'] = count($stocks) > 0
                ? max($stocks)
                : 0;
        }

        unset($classData);

        return $classes;
    }

    private function emptyAbcClass(string $class, string $label, string $description): array
    {
        return [
            'class' => $class,
            'label' => $label,
            'description' => $description,

            'product_count' => 0,
            'inventory_value' => 0.0,
            'value_share_percent' => 0.0,

            'avg_current_stock' => 0.0,
            'min_current_stock' => 0.0,
            'max_current_stock' => 0.0,

            'low_stock_count' => 0,
            'out_of_stock_count' => 0,

            'products' => [],
        ];
    }

    private function calculateGiniCoefficient(array $values): float
    {
        $values = array_values(array_filter($values, function ($value) {
            return $value >= 0;
        }));

        $n = count($values);

        if ($n === 0) {
            return 0.0;
        }

        sort($values, SORT_NUMERIC);

        $total = array_sum($values);

        if ($total <= 0) {
            return 0.0;
        }

        $weightedSum = 0.0;

        foreach ($values as $index => $value) {
            $rank = $index + 1;
            $weightedSum += $rank * $value;
        }

        $gini = ((2 * $weightedSum) / ($n * $total)) - (($n + 1) / $n);

        return round($gini, 4);
    }

    private function resolveGiniLabel(float $gini): string
    {
        if ($gini < 0.3) {
            return 'Faible concentration de valeur';
        }

        if ($gini < 0.6) {
            return 'Concentration modérée de valeur';
        }

        return 'Forte concentration de valeur';
    }

    public function abcClass(string $class): array
    {
        $class = strtoupper(trim($class));

        if (!in_array($class, ['A', 'B', 'C'], true)) {
            return ApiResponse::success(
                'ABC class dashboard fetched successfully',
                [
                    'class' => null,
                    'kpis' => null,
                    'charts' => [
                        'stock_status_distribution' => [],
                        'top_products_by_value' => [],
                        'stock_value_distribution' => [],
                        'movement_distribution' => [],
                    ],
                    'products' => [],
                ]
            );
        }

        /*
        * Reuse the same base calculation as the global ABC endpoint.
        */
        $rows = $this->dashboardStockRepository->abcBaseProducts();

        $products = [];

        foreach ($rows as $row) {
            $currentStock = (float) $row['current_stock'];
            $unitCost = (float) $row['latest_unit_purchase_cost'];
            $stockValue = round($currentStock * $unitCost, 2);

            $stockOutQuantity = (float) $row['stock_out_quantity'];
            $stockInQuantity = (float) $row['stock_in_quantity'];

            $openingStock = $currentStock - $stockInQuantity + $stockOutQuantity;
            $averageStock = ($openingStock + $currentStock) / 2;

            if ($averageStock < 0) {
                $averageStock = 0;
            }

            $stockRotationRate = $this->calculateStockRotationRate(
                $stockOutQuantity,
                $averageStock
            );

            $averageDailyConsumption = $this->calculateAverageDailyConsumption(
                $stockOutQuantity,
                360
            );

            $stockCoverageDays = $this->calculateStockCoverageDays(
                $currentStock,
                $averageDailyConsumption
            );

            $averageStorageDurationDays = $this->calculateAverageStorageDurationDays(
                360,
                $stockRotationRate
            );

            $products[] = [
                'id' => $row['id'],
                'name' => $row['name'],
                'sku' => $row['sku'],
                'category' => $row['category'],

                'current_stock' => round($currentStock, 4),
                'low_stock_threshold' => $row['low_stock_threshold'],
                'stock_status' => $this->resolveStockStatus(
                    $currentStock,
                    (float) $row['low_stock_threshold']
                ),

                'latest_unit_purchase_cost' => round($unitCost, 4),
                'stock_value' => $stockValue,

                'stock_in_quantity' => round($stockInQuantity, 4),
                'stock_out_quantity' => round($stockOutQuantity, 4),
                'movement_count' => $row['movement_count'],
                'last_movement_at' => $row['last_movement_at'],

                'stock_rotation_rate' => $stockRotationRate,
                'average_daily_consumption' => $averageDailyConsumption,
                'stock_coverage_days' => $stockCoverageDays,
                'average_storage_duration_days' => $averageStorageDurationDays,
                'stock_flow_time_days' => $averageStorageDurationDays,
                'has_consumption_data' => $stockOutQuantity > 0,
            ];
        }

        usort($products, function (array $a, array $b) {
            return $b['stock_value'] <=> $a['stock_value'];
        });

        $totalInventoryValue = array_sum(array_column($products, 'stock_value'));
        $cumulativeValue = 0.0;

        foreach ($products as &$product) {
            $valueSharePercent = $totalInventoryValue > 0
                ? ($product['stock_value'] / $totalInventoryValue) * 100
                : 0;

            $cumulativeValue += $valueSharePercent;

            $product['value_share_percent'] = round($valueSharePercent, 2);
            $product['cumulative_value_percent'] = round($cumulativeValue, 2);
            $product['abc_class'] = $this->resolveAbcClass($cumulativeValue);
        }

        unset($product);

        $classProducts = array_values(array_filter($products, function (array $product) use ($class) {
            return $product['abc_class'] === $class;
        }));

        $classSummary = $this->buildSingleAbcClassSummary(
            $class,
            $classProducts,
            $totalInventoryValue
        );

        return ApiResponse::success(
            'ABC class dashboard fetched successfully',
            [
                'class' => [
                    'class' => $class,
                    'label' => $classSummary['label'],
                    'description' => $classSummary['description'],
                ],

                'kpis' => [
                    'product_count' => $classSummary['product_count'],
                    'inventory_value' => $classSummary['inventory_value'],
                    'value_share_percent' => $classSummary['value_share_percent'],

                    'avg_current_stock' => $classSummary['avg_current_stock'],
                    'min_current_stock' => $classSummary['min_current_stock'],
                    'max_current_stock' => $classSummary['max_current_stock'],

                    'low_stock_count' => $classSummary['low_stock_count'],
                    'out_of_stock_count' => $classSummary['out_of_stock_count'],

                    'avg_rotation_rate' => $classSummary['avg_rotation_rate'],
                    'avg_coverage_days' => $classSummary['avg_coverage_days'],
                    'avg_storage_duration_days' => $classSummary['avg_storage_duration_days'],
                ],

                'charts' => [
                    'stock_status_distribution' => $this->buildStockStatusDistribution($classProducts),
                    'top_products_by_value' => $this->buildTopProductsByValue($classProducts),
                    'stock_value_distribution' => $this->buildStockValueDistribution($classProducts),
                    'movement_distribution' => $this->buildMovementDistribution($classProducts),
                ],

                'products' => $classProducts,
            ]
        );
    }

    private function buildSingleAbcClassSummary(string $class, array $products, float $totalInventoryValue): array
    {
        $metadata = [
            'A' => [
                'label' => 'Classe A',
                'description' => 'Articles critiques à forte valeur',
            ],
            'B' => [
                'label' => 'Classe B',
                'description' => 'Articles à valeur intermédiaire',
            ],
            'C' => [
                'label' => 'Classe C',
                'description' => 'Articles à faible valeur individuelle',
            ],
        ];

        $inventoryValue = array_sum(array_column($products, 'stock_value'));
        $stocks = array_column($products, 'current_stock');

        $rotationRates = array_values(array_filter(
            array_column($products, 'stock_rotation_rate'),
            fn ($value) => $value !== null
        ));

        $coverageDays = array_values(array_filter(
            array_column($products, 'stock_coverage_days'),
            fn ($value) => $value !== null
        ));

        $storageDurations = array_values(array_filter(
            array_column($products, 'average_storage_duration_days'),
            fn ($value) => $value !== null
        ));

        $lowStockCount = 0;
        $outOfStockCount = 0;

        foreach ($products as $product) {
            if ($product['stock_status'] === 'LOW_STOCK') {
                $lowStockCount++;
            }

            if ($product['stock_status'] === 'OUT_OF_STOCK') {
                $outOfStockCount++;
            }
        }

        return [
            'class' => $class,
            'label' => $metadata[$class]['label'],
            'description' => $metadata[$class]['description'],

            'product_count' => count($products),
            'inventory_value' => round($inventoryValue, 2),
            'value_share_percent' => $totalInventoryValue > 0
                ? round(($inventoryValue / $totalInventoryValue) * 100, 2)
                : 0,

            'avg_current_stock' => count($stocks) > 0
                ? round(array_sum($stocks) / count($stocks), 4)
                : 0,

            'min_current_stock' => count($stocks) > 0
                ? min($stocks)
                : 0,

            'max_current_stock' => count($stocks) > 0
                ? max($stocks)
                : 0,

            'low_stock_count' => $lowStockCount,
            'out_of_stock_count' => $outOfStockCount,

            'avg_rotation_rate' => count($rotationRates) > 0
                ? round(array_sum($rotationRates) / count($rotationRates), 4)
                : null,

            'avg_coverage_days' => count($coverageDays) > 0
                ? round(array_sum($coverageDays) / count($coverageDays), 2)
                : null,

            'avg_storage_duration_days' => count($storageDurations) > 0
                ? round(array_sum($storageDurations) / count($storageDurations), 2)
                : null,
        ];
    }

    private function buildStockStatusDistribution(array $products): array
    {
        $counts = [
            'OK' => 0,
            'LOW_STOCK' => 0,
            'OUT_OF_STOCK' => 0,
        ];

        foreach ($products as $product) {
            $status = $product['stock_status'];

            if (isset($counts[$status])) {
                $counts[$status]++;
            }
        }

        return [
            [
                'status' => 'OK',
                'label' => 'Stock normal',
                'value' => $counts['OK'],
            ],
            [
                'status' => 'LOW_STOCK',
                'label' => 'Stock faible',
                'value' => $counts['LOW_STOCK'],
            ],
            [
                'status' => 'OUT_OF_STOCK',
                'label' => 'Rupture de stock',
                'value' => $counts['OUT_OF_STOCK'],
            ],
        ];
    }

    private function buildTopProductsByValue(array $products, int $limit = 5): array
    {
        usort($products, function (array $a, array $b) {
            return $b['stock_value'] <=> $a['stock_value'];
        });

        return array_slice(array_map(function (array $product) {
            return [
                'id' => $product['id'],
                'name' => $product['name'],
                'sku' => $product['sku'],
                'stock_value' => $product['stock_value'],
                'value_share_percent' => $product['value_share_percent'],
            ];
        }, $products), 0, $limit);
    }

    private function buildStockValueDistribution(array $products): array
    {
        return array_map(function (array $product) {
            return [
                'id' => $product['id'],
                'name' => $product['name'],
                'sku' => $product['sku'],
                'stock_value' => $product['stock_value'],
            ];
        }, $products);
    }

    private function buildMovementDistribution(array $products): array
    {
        $stockIn = array_sum(array_column($products, 'stock_in_quantity'));
        $stockOut = array_sum(array_column($products, 'stock_out_quantity'));

        return [
            [
                'label' => 'Entrées',
                'type' => 'in',
                'value' => round($stockIn, 4),
            ],
            [
                'label' => 'Sorties',
                'type' => 'out',
                'value' => round($stockOut, 4),
            ],
        ];
    }
}