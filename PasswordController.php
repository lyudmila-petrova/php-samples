<?php

/**
 * Консольный контроллер для сброса паролей
 * Логика выносится в отдельный сервисный слой *
 */

namespace console\controllers\user;

use domain\entities\User\User;
use domain\services\manage\UserManageService;
use yii\console\Controller;
use yii\console\Exception;

/**
 * Interactive console user status manager
 */
class PasswordController extends Controller
{
    public $defaultAction = 'reset';
    private $service;

    public function __construct($id, $module, UserManageService $service, $config = [])
    {
        parent::__construct($id, $module, $config);
        $this->service = $service;
    }

    /**
     * User activation
     */
    public function actionReset(): void
    {
        $username = $this->prompt('Username:', ['required' => true]);
        $user = $this->findModel($username);
        $password = $this->prompt('Password:', ['required' => true]);
        $this->service->setPassword($user->id, $password);
        $this->stdout('Done!' . PHP_EOL);
    }

    private function findModel($username): User
    {
        if (!$model = User::findOne(['username' => $username])) {
            throw new Exception('User is not found');
        }
        return $model;
    }
}
