<?php

/**
 * Вьюха для просмотра всех цветов на сайте и маппинга на ближайший цвет фиксированной палитры
 */

use domain\entities\Palette\Color;
use domain\helpers\PaletteHelper;
use yii\helpers\Html;
use yii\grid\GridView;

/* @var $this yii\web\View */
/* @var $searchModel \backend\forms\Palette\ColorSearch */
/* @var $dataProvider yii\data\ActiveDataProvider */

$this->title = 'Colors';
$this->params['breadcrumbs'][] = ['label' => 'Palette Summary', 'url' => ['palette/palette']];
$this->params['breadcrumbs'][] = $this->title;

?>
<div class="yml-feed-index">

    <h1><?= Html::encode($this->title) ?></h1>
    <p>
        <?= Html::a('Create New Color', ['create'], ['class' => 'btn btn-success']) ?>
    </p>

    <?= GridView::widget([
        'dataProvider' => $dataProvider,
        'filterModel' => $searchModel,
        'columns' => [
            [
                'attribute' => 'id',
                'headerOptions' => ['style' => 'width:70px;'],
            ],
            [
                'attribute' => 'name',
            ],
            [
                'attribute' => 'hex',
                'value' => function(Color $color) {
                    return PaletteHelper::showColor($color->hex, $color->name) . '&nbsp;&nbsp;' . $color->hex;
                },
                'format' => 'raw',
            ],
            [
                'attribute' => 'palette_color_id',
                'value' => function(Color $color) {
                    return $color->paletteColor
                        ? PaletteHelper::showColor($color->paletteColor->hex, $color->paletteColor->name) . '&nbsp;&nbsp;' . $color->paletteColor->hex
                        : "";
                },
                'filter' => PaletteHelper::getSimplePaletteColorsListOptions(),
                'format' => 'raw',
            ],
            [
                'attribute' => 'locked',
                'format'=>'boolean',
            ],
            ['class' => 'yii\grid\ActionColumn'],
        ],
    ]); ?>
</div>
