<?php
/**
 * Created by PhpStorm.
 * User: georgy
 * Date: 14.12.14
 * Time: 0:20
 */

namespace backend\models;

use common\models\ServerUser;
use yii\base\Model;

class ServerUserForm extends Model
{
    public $title;
    public $user_login;
    public $user_pass;
    public $ftp_host;
    public $ftp_login;
    public $ftp_pass;

    public function __construct($action = null)
    {
        parent::__construct();
    }

    /**
     * {@inheritdoc}
     */
    public function rules(): array
    {
        return [
            [['title', 'user_login', 'user_pass'], 'required'],
            [['title', 'ftp_host'], 'string', 'max' => 255],
            [['user_login', 'user_pass', 'ftp_login', 'ftp_pass'], 'string', 'max' => 128]
        ];
    }

    public function attributeLabels(): array
    {
        return [
            'title' => \Yii::t('backend', 'Title'),
            'user_login' => \Yii::t('backend', 'User login'),
            'user_pass' => \Yii::t('backend', 'User pass'),
            'ftp_host' => \Yii::t('backend', 'FTP host'),
            'ftp_login' => \Yii::t('backend', 'FTP login'),
            'ftp_pass' => \Yii::t('backend', 'FTP pass'),
        ];
    }

    public function save(ServerUser $server_user, array $data): bool
    {
        $isLoad = $server_user->load([
            'title' => $data['title'],
            'user_login' => $data['user_login'],
            'user_pass' => $data['user_pass'],
            'ftp_host' => $data['ftp_host'],
            'ftp_login' => $data['ftp_login'],
            'ftp_pass' => $data['ftp_pass'],
        ], '');

        return ($isLoad && $server_user->save());
    }
}
