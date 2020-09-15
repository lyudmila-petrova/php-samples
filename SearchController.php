<?php

/**
 * Консольный контроллер для создания поискового индекса, частичной/полной индексации каталога товаров
 */

namespace console\controllers;


use domain\entities\Catalog\Offer;
use domain\entities\Catalog\Product;
use domain\lib\search\ProductIndexer;
use domain\lib\search\ProductMapper;
use Yii;
use yii\console\Controller;
use yii\helpers\Console;

class SearchController extends Controller
{
    private $indexer;
    private $mapper;

    public function __construct(
        $id,
        $module,
        ProductIndexer $indexer,
        ProductMapper $mapper,
        array $config = [])
    {
        parent::__construct($id, $module, $config);
        $this->indexer = $indexer;
        $this->mapper = $mapper;
    }

    public function actionRemap() {

        if (Console::confirm("All indexed data will be lost. Are you sure")) {
            $this->mapper->delete();
            $this->mapper->create();
            $this->stdout('Index map recreated.' . PHP_EOL);
        } else {
            $this->stdout('Cancelled.' . PHP_EOL);
        }

        $this->showTotalTime();
    }

    public function actionReindexAll(): void
    {
        $query = Product::find()
            ->where(['excluded' => 0])
            ->andWhere(['available' => 1])
            ->with(['category', 'offers'])
            ->orderBy('id');

        $this->stdout('Clearing' . PHP_EOL);

        $this->indexer->clear();

        $this->stdout('Indexing of products' . PHP_EOL);

        foreach ($query->each() as $product) {
            /** @var Product $product */
            $this->stdout('Product #' . $product->id . PHP_EOL);
            $this->indexer->index($product);
        }

        $this->stdout('Done!' . PHP_EOL);
        $this->showTotalTime();
    }

    public function actionReindexFeed(string $feed_name): void
    {
        $product_ids = Offer::find()
            ->select('product_id')
            ->where(['feed_name' => $feed_name])
            ->andWhere(['available' => 1])
            ->groupBy('product_id')
            ->column();

        $query = Product::find()
            ->where(['excluded' => 0])
            ->andWhere(['id' => $product_ids])
            ->with(['category', 'offers'])
            ->orderBy('id');

        $this->stdout('Clearing of <' . $feed_name . '>' . PHP_EOL);

        $this->indexer->clearByFeedName($feed_name);

        $this->stdout('Indexing of products for <' . $feed_name . '>' . PHP_EOL);

        foreach ($query->each() as $product) {
            /** @var Product $product */
            $this->stdout('Product #' . $product->id . PHP_EOL);
            $this->indexer->index($product);
        }

        $this->stdout('Done!' . PHP_EOL);
        $this->showTotalTime();
    }

    public function actionIndex($id): void
    {
        $product = Product::findOne($id);

        /** @var Product $product */
        $this->stdout('Product #' . $product->id . PHP_EOL);
        $this->indexer->index($product);

        $this->stdout('Done!' . PHP_EOL);
        $this->showTotalTime();
    }

    private function showTotalTime(): void
    {
        $this->stdout('Total (sec.): ' . Yii::getLogger()->getElapsedTime() . PHP_EOL);
    }
}
