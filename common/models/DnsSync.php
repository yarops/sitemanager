<?php

namespace common\models;

use yii\db\ActiveQuery;
use yii\db\ActiveRecord;
use yii\web\NotFoundHttpException;

/**
 * DNS sync ISPmanager → Cloudflare for the sites of one server.
 *
 * @property int $id
 * @property string $title
 * @property int $source_server_id
 * @property int $isp_server_id
 * @property int|null $created_by
 * @property string $created_at
 *
 * @property Server $sourceServer
 * @property Server $ispServer
 * @property User|null $author
 * @property DnsSyncZone[] $zones
 */
class DnsSync extends ActiveRecord
{
    public static function tableName(): string
    {
        return '{{%dns_sync}}';
    }

    public function rules(): array
    {
        return [
            [['title', 'source_server_id', 'isp_server_id', 'created_at'], 'required'],
            [['title'], 'string', 'max' => 255],
            [['source_server_id', 'isp_server_id', 'created_by'], 'integer'],
            [['source_server_id', 'isp_server_id'], 'exist', 'targetClass' => Server::class, 'targetAttribute' => 'id'],
            [['created_at'], 'safe'],
        ];
    }

    public function attributeLabels(): array
    {
        return [
            'id' => 'ID',
            'title' => 'Название',
            'source_server_id' => 'Сервер сайтов',
            'isp_server_id' => 'ISPmanager',
            'created_by' => 'Автор',
            'created_at' => 'Создан',
        ];
    }

    public function getSourceServer(): ActiveQuery
    {
        return $this->hasOne(Server::class, ['id' => 'source_server_id']);
    }

    public function getIspServer(): ActiveQuery
    {
        return $this->hasOne(Server::class, ['id' => 'isp_server_id']);
    }

    public function getAuthor(): ActiveQuery
    {
        return $this->hasOne(User::class, ['id' => 'created_by']);
    }

    public function getZones(): ActiveQuery
    {
        return $this->hasMany(DnsSyncZone::class, ['sync_id' => 'id'])->orderBy(['domain' => SORT_ASC]);
    }

    /**
     * @throws NotFoundHttpException
     */
    public static function findById(int $id): DnsSync
    {
        if (($model = DnsSync::findOne($id)) !== null) {
            return $model;
        }

        throw new NotFoundHttpException('The requested sync does not exist.');
    }
}
