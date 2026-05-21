<?php

namespace common\models;

use Yii;
use yii\db\ActiveQuery;
use yii\db\ActiveRecord;
use yii\web\NotFoundHttpException;

/**
 * ServerUser model
 *
 * @property int $id
 * @property int $server_id идентификатор родительского комментария
 * @property string $title заголовок
 * @property string $user_login логин
 * @property string $user_pass пароль
 * @property string $ftp_login
 * @property string $ftp_pass
 * @property string $editor_ftp_login
 * @property string $editor_ftp_pass
 * @property int $is_archived
 * @property string|null $archived_at
 * @property int|null $archived_by
 *
 * @property Server $server
 */
class ServerUser extends ActiveRecord
{
    public const STATUS_MODERATE = 'moderate';
    public const STATUS_PUBLISH = 'publish';

    /**
     * @inheritdoc
     */
    public static function tableName(): string
    {
        return '{{%server_user}}';
    }

    /**
     * @inheritdoc
     */
    public function rules(): array
    {
        return [
            [['id', 'server_id', 'is_archived', 'archived_by'], 'integer'],
            [['title', 'user_login', 'user_pass'], 'required'],
            [['archived_at'], 'safe'],
            [['title'], 'string', 'max' => 255],
            [['user_login', 'user_pass', 'ftp_login', 'ftp_pass', 'editor_ftp_login', 'editor_ftp_pass'], 'string', 'max' => 128]
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels(): array
    {
        return [
            'id' => Yii::t('backend', 'ID'),
            'server_id' => Yii::t('backend', 'Server ID'),
            'title' => Yii::t('backend', 'Title'),
            'user_login' => Yii::t('backend', 'User login'),
            'user_pass' => Yii::t('backend', 'User pass'),
            'ftp_login' => Yii::t('backend', 'FTP login'),
            'ftp_pass' => Yii::t('backend', 'FTP pass'),
            'editor_ftp_login' => Yii::t('backend', 'Editor FTP login'),
            'editor_ftp_pass' => Yii::t('backend', 'Editor FTP pass'),
            'is_archived' => Yii::t('backend', 'Archived'),
            'archived_at' => Yii::t('backend', 'Archived at'),
            'archived_by' => Yii::t('backend', 'Archived by'),
        ];
    }

    public function getServer(): ActiveQuery
    {
        return $this->hasOne(Server::class, ['id' => 'server_id']);
    }

    /**
     * @throws NotFoundHttpException
     */
    public static function findById(int $id, bool $ignorePublishStatus = false): ServerUser
    {
        if (($model = ServerUser::findOne($id)) !== null) {
            return $model;
        }

        throw new NotFoundHttpException('The requested ServerUser does not exist.');
    }

    /**
     * @throws NotFoundHttpException
     */
    public static function findByServerId(int $server_id, bool $ignorePublishStatus = false): array
    {
        $query = ServerUser::find()->where(['server_id' => $server_id]);
        if (!$ignorePublishStatus) {
            $query->andWhere(['is_archived' => 0]);
        }

        if (($server_users = $query->all()) !== null) {
            return $server_users;
        }

        throw new NotFoundHttpException('The requested ServerUser does not exist.');
    }

    public function isArchived(): bool
    {
        return (int)$this->is_archived === 1;
    }

    public function archive(?int $userId = null): bool
    {
        $this->is_archived = 1;
        $this->archived_at = date('Y-m-d H:i:s');
        $this->archived_by = $userId;

        return $this->save(false);
    }

    public function restore(): bool
    {
        $this->is_archived = 0;
        $this->archived_at = null;
        $this->archived_by = null;

        return $this->save(false);
    }
}
