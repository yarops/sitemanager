<?php

namespace common\models;

use Yii;
use yii\data\ActiveDataProvider;
use yii\db\ActiveQuery;
use yii\db\ActiveRecord;
use yii\helpers\ArrayHelper;
use yii\web\NotFoundHttpException;

/**
 * Post model.
 *
 * @property string $id
 * @property string $parent_id
 * @property string $protocol
 * @property string $domain
 * @property string $alias
 * @property string $admin_link
 * @property string $content
 * @property string $server_id
 * @property string $server_user_id
 * @property string $template_id
 * @property integer $check_enabled
 * @property integer $check_interval
 * @property string $notify_strategy
 * @property string $next_check_at
 * @property string $author_id
 * @property string $publish_status
 * @property string $publish_date
 *
 * @property Item $parent
 * @property Item $childs
 * @property User $author
 * @property Server $server
 * @property ServerUser $serverUser
 * @property Template $template
 * @property Check $lastCheck
 */
class Item extends ActiveRecord
{
    public const STATUS_PUBLISH = 'publish';
    public const STATUS_DRAFT   = 'draft';

    public const NOTIFY_IMMEDIATE = 'immediate';
    public const NOTIFY_SUMMARY   = 'summary';
    public const NOTIFY_DISABLED  = 'disabled';

    /**
     * Table name.
     *
     * @inheritdoc
     */
    public static function tableName(): string
    {
        return '{{%item}}';
    }

    /**
     * Rules.
     *
     * @inheritdoc
     */
    public function rules(): array
    {
        return [
            [ [ 'domain', 'protocol' ], 'required' ],
            [ [ 'alias' ], 'string' ],
            [ [ 'parent_id', 'server_id', 'server_user_id', 'template_id', 'author_id' ], 'integer' ],
            [ [ 'admin_link', 'content', 'publish_status' ], 'string' ],
            [ [ 'publish_date', 'next_check_at' ], 'safe' ],
            [
                [ 'protocol' ],
                'string',
                'max' => 8,
            ],
            [
                [ 'domain' ],
                'string',
                'max' => 255,
            ],
            [ [ 'check_enabled' ], 'integer' ],
            [ [ 'check_interval' ], 'integer', 'min' => 1 ],
            [ [ 'notify_strategy' ], 'string', 'max' => 32 ],
            [ [ 'notify_strategy' ], 'in', 'range' => [self::NOTIFY_IMMEDIATE, self::NOTIFY_SUMMARY, self::NOTIFY_DISABLED] ],
        ];
    }

    /**
     * Attribute labels.
     *
     * @inheritdoc
     */
    public function attributeLabels(): array
    {
        return [
            'id'             => Yii::t('backend', 'ID'),
            'parent_id'             => Yii::t('backend', 'Parent ID'),
            'domain'         => Yii::t('backend', 'Domain'),
            'alias'         => Yii::t('backend', 'Alias'),
            'protocol'       => Yii::t('backend', 'Protocol'),
            'admin_link'     => Yii::t('backend', 'Admin link'),
            'content'        => Yii::t('backend', 'Content'),
            'server_id'      => Yii::t('backend', 'Server ID'),
            'server_user_id' => Yii::t('backend', 'Server User ID'),
            'server'         => Yii::t('backend', 'Server'),
            'server_user'    => Yii::t('backend', 'Server user'),
            'template_id'    => Yii::t('backend', 'Template ID'),
            'template'       => Yii::t('backend', 'Template'),
            'check_enabled'  => Yii::t('backend', 'Check enabled'),
            'check_interval' => Yii::t('backend', 'Check interval (minutes)'),
            'notify_strategy' => Yii::t('backend', 'Notification strategy'),
            'next_check_at'  => Yii::t('backend', 'Next check time'),
            'author'         => Yii::t('backend', 'Author'),
            'author_id'      => Yii::t('backend', 'Author ID'),
            'publish_status' => Yii::t('backend', 'Publish status'),
            'publish_date'   => Yii::t('backend', 'Publish date'),
        ];
    }

    public function getParent(): ActiveQuery
    {
        return $this->hasMany(Item::class, [ 'id' => 'parent_id' ]);
    }

    public function getChilds(): ActiveQuery
    {
        return $this->hasMany(Item::class, [ 'parent_id' => 'id' ]);
    }

    public function getAuthor(): ActiveQuery
    {
        return $this->hasOne(User::class, [ 'id' => 'author_id' ]);
    }

    public function getServer(): ActiveQuery
    {
        return $this->hasOne(Server::class, [ 'id' => 'server_id' ]);
    }

    public function getServerUser(): ActiveQuery
    {
        return $this->hasOne(ServerUser::class, [ 'id' => 'server_user_id' ]);
    }

    public function getTemplate(): ActiveQuery
    {
        return $this->hasOne(Template::class, [ 'id' => 'template_id' ]);
    }

    public function getLastCheck(): ActiveQuery
    {
        return $this->hasOne(Check::class, [ 'item_id' => 'id' ])
            ->orderBy(['check_date' => SORT_DESC]);
    }

    public static function findPublished(string $statusFilter = null): ActiveDataProvider
    {
        $query = self::find()
            ->where([ 'publish_status' => self::STATUS_PUBLISH ])
            ->with('lastCheck');

        // Apply status filter if specified
        if ($statusFilter) {
            $query = self::applyStatusFilter($query, $statusFilter);
        }

        // Default sort by publish date
        $query->orderBy([ 'publish_date' => SORT_DESC ]);

        return new ActiveDataProvider([
            'query' => $query,
        ]);
    }

    /**
     * Apply status filter to query
     */
    private static function applyStatusFilter($query, $status)
    {
        switch ($status) {
            case 'enabled':
                return $query->andWhere(['check_enabled' => 1]);
            case 'disabled':
                return $query->andWhere(['check_enabled' => 0]);
            case 'up':
                return $query->andWhere(['check_enabled' => 1])
                    ->leftJoin('`check`', '`check`.item_id = item.id AND `check`.check_date = (
                        SELECT MAX(check_date) FROM `check` WHERE `check`.item_id = item.id
                    )')
                    ->andWhere(['`check`.check_status' => '200']);
            case 'down':
                return $query->andWhere(['check_enabled' => 1])
                    ->leftJoin('`check`', '`check`.item_id = item.id AND `check`.check_date = (
                        SELECT MAX(check_date) FROM `check` WHERE `check`.item_id = item.id
                    )')
                    ->andWhere(['!=', '`check`.check_status', '200'])
                    ->andWhere(['not', ['`check`.check_status' => null]]);
            case 'never_checked':
                return $query->andWhere(['check_enabled' => 1])
                    ->leftJoin('`check`', '`check`.item_id = item.id')
                    ->andWhere(['`check`.check_status' => null]);
            default:
                return $query;
        }
    }

    public static function findByCurrentUser(): ActiveDataProvider
    {
        return new ActiveDataProvider(
            [
                'query' => self::find()
                ->where(
                    [
                        'publish_status' => self::STATUS_PUBLISH,
                        'author_id'      => Yii::$app->user->id,
                    ]
                )
                ->orderBy([ 'publish_date' => SORT_DESC ]),
            ]
        );
    }

    /**
     * Find item by id.
     *
     * @param int $id
     * @param bool $ignorePublishStatus
     *
     * @return Item
     * @throws NotFoundHttpException
     */
    public static function findById(int $id, bool $ignorePublishStatus = false): Item
    {
        if (( $model = self::findOne($id) ) !== null) {
            if ($model->isPublished() || $ignorePublishStatus) {
                return $model;
            }
        }

        throw new NotFoundHttpException('The requested item does not exist.');
    }

    /**
     * Find items by ids.
     *
     * @param array $ids
     *
     * @return array
     * @throws NotFoundHttpException
     */
    public static function findByIds(array $ids): array
    {
        if (( $items = self::findAll($ids) ) !== null) {
            return $items;
        }

        throw new NotFoundHttpException('The requested item does not exist.');
    }

    /**
     * Find items by server id.
     *
     * @param int $server_id
     * @param bool $ignorePublishStatus
     *
     * @return array
     * @throws NotFoundHttpException
     */
    public function findByServerId(int $server_id, bool $ignorePublishStatus = false): array
    {
        if (( $items = self::find()->where([ 'server_id' => $server_id ])->all() ) !== null) {
            return $items;
        }

        throw new NotFoundHttpException('The requested ServerUser does not exist.');
    }

    protected function isPublished(): bool
    {
        return $this->publish_status === self::STATUS_PUBLISH;
    }

    public function afterSave($insert, $changedAttributes)
    {
        parent::afterSave($insert, $changedAttributes);

        // Инициализируем next_check_at при первом включении мониторинга или изменении интервала
        if ($this->check_enabled && ($insert || isset($changedAttributes['check_enabled']) || isset($changedAttributes['check_interval']))) {
            if (empty($this->next_check_at)) {
                $this->next_check_at = date('Y-m-d H:i:s');
                $this->updateAttributes(['next_check_at' => $this->next_check_at]);
            }
        }
    }
}
