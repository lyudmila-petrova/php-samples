<?php

/**
 * Часть сайта для фотографа
 * Здесь логика запросов к БД
 */

namespace domain\repositories;

use domain\entities\Site\Gallery;
use domain\entities\Site\Photo;
use yii\db\Query;

class GalleryReadRepository
{
    public function find($id): Gallery
    {
        return $gallery = Gallery::findOne($id);
    }

    public function existsByService($id): bool
    {
        return Gallery::find()
            ->where(['service_id' => $id])
            ->exists();
    }

    public function getCountGalleriesByServicesQuery(): Query {

        $query = new Query;
        $query->select('services.id, COUNT(*) AS cnt')
            ->from('galleries')
            ->leftJoin('services','services.id=galleries.service_id')
            ->groupBy('services.id')
            ->indexBy ('services.id');
        return $query;
    }

    public function getAll() {
        return Gallery::find()
            ->leftJoin('services','services.id=galleries.service_id')
            ->orderBy('services.sort')
            ->all();
    }

    public function findByService($id) {
        return Gallery::find()
            ->where(['service_id' => $id])
            ->all();
    }

    public function getLastPhotos($limit = 10) {
        return Photo::find()
            ->where(['not', ['created_at' => null]])
            ->orderBy(['created_at' => SORT_DESC])
            ->limit($limit)
            ->all();
    }
}
