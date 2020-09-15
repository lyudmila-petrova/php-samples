<?php
/**
 * Вспомогательный кусок проекта: предобработка и кэширование правил
 * вычисления категории товара
 *
 * Идея:
 * массовый импорт товаров
 * назначать категорию товара на основе названия и описания товара по правилам
 * правила описаны в виде грамматики
 * у каждого товара должна быть одна основная категория и сколько угодно дополнительных
 *
 */


namespace domain\helpers;

use domain\entities\Catalog\Category;
use NXP\Stemmer;
use Yii;

class CalculateCategoryHelper
{
    const CATEGORIES_SETTINGS_CACHE_KEY = 'all_categories_settings';

    public const EMPTY_WORDS = [
        'для',
        'про',
        'и',
        'с',
        'на',
        'в'
    ];

    public static function getSettings(): array {
        $data = Yii::$app->cache->getOrSet(self::CATEGORIES_SETTINGS_CACHE_KEY, function () {
            return self::calculateSettings();
        });

        return $data;
    }

    public static function clearCache() {
        Yii::$app->cache->delete(self::CATEGORIES_SETTINGS_CACHE_KEY);
    }

    private static function calculateSettings(): array {
        $categories_settings = [
            'main' => [],
            'extra' => []
        ];

        $categories = self::getAllCategories();

        foreach ($categories as $category_id => $category) {
            $categories[$category_id]->rule = self::stemRule(self::getCategoryRule($category));
        }

        $categories_parents = CategoryHelper::getAllAncestorIds();

        foreach ($categories_parents as $category_id => $path_ids) {
            $settings = [
                'required_rules' => [],
                'rules' => [],
            ];

            $is_main = false;

            foreach ($path_ids as $id) {
                /* @var $category Category */
                $category = $categories[$id];
                if($category->main) {
                    $is_main = true;
                }

                if($category->is_required_rule) {
                    $settings['required_rules'][$category->rule] = $category->rule_weight;
                } else {
                    $settings['rules'][$category->rule] = $category->rule_weight;
                }
            }

            if($is_main) {
                $categories_settings['main'][$category_id] = $settings;
            } else {
                $categories_settings['extra'][$category_id] = $settings;
            }
        }

        return $categories_settings;
    }

    /**
     * @return array
     */
    private static function getAllCategories()
    {
        $nodes = Category::find()
            ->indexBy('id')
            ->orderBy(['lft' => SORT_ASC])
            ->asArray()
            ->all();

        $result = [];
        foreach($nodes as $category_id => $array) {
            $result[$category_id] = (object) $array;
        }

        return $result;
    }

    private static function getCategoryRule($category): string
    {
        $rule_string = $category->rule
            ? $category->rule
            : $category->name;

        return $rule_string;
    }

    private static function toWordRegexArray($words): array
    {
        return array_map(
            function($word) { return '/\b' .$word . '\b/u'; },
            $words
        );
    }

    public static function stemRule($rule_string)
    {
        $pattern = '/[а-яА-ЯёЁ\w\-]+/u';

        $rule_string = mb_strtolower($rule_string, 'utf-8');

        $rule_string = preg_replace(
            self::toWordRegexArray(self::EMPTY_WORDS),
            '',
            $rule_string);
        $rule_string = preg_replace('/\b(\s+)\b/u', ', ', $rule_string);
        $rule_string = preg_replace('!\s+!', ' ', $rule_string);
        $rule_string = trim($rule_string);

        preg_match_all($pattern, $rule_string, $matches);
        $words = $matches[0];

        $replacements = [];
        $stemmer = new Stemmer();
        foreach ($words as $word) {
            if(is_numeric($word))
                continue;

            $replacements[$word] = $stemmer->getWordBase($word);
        }

        $stemmed_rule = str_replace(array_keys($replacements), array_values($replacements), $rule_string);

        return $stemmed_rule;
    }
}
