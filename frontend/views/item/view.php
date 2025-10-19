<?php

/**
 * Created by PhpStorm.
 * User: georgy
 * Date: 09.07.14
 * Time: 9:26
 */

use yii\helpers\Html;

/* @var $model common\models\Item */
/* @var $userItemRelation common\models\UserItemRelation */
/* @var $acceptedUsers array */
/* @var $users common\models\User */

$this->title = $model->domain;
$this->params['breadcrumbs'][] = ['label' => Yii::t('frontend', 'Items'), 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;

$has_subdomains = is_array($model->childs) && !empty($model->childs);

?>
<h1 class="page-title">
    <?= $model->domain ?>
    <a href="<?= $model->protocol . '://' . $model->domain; ?>" target="_blank" class="btn btn-success btn-xs"
       style="margin-right: 8px;">To site</a>
    <a href="<?= $model->protocol . '://' . $model->domain . $model->admin_link; ?>" target="_blank"
       class="btn btn-success btn-xs">To admin</a>
</h1>

<div class="row">
    <div class="col-sm-9">
        <div class="box">
            <div class="box__heading">
                <h2 class="box__title">Common info</h2>
                <a href="#" class="copy__link js-copy-all-link">
                    Copy all
                    <svg class="icon" width="20px" height="20px">
                        <use xlink:href="#icon-copy"></use>
                    </svg>
                </a>
            </div>
            <ul class="cred">
                <?php if ($model->alias) : ?>
                    <li>
                        <b>Alias:</b>
                        <div class="copy">
                            <div
                                class="copy__value js-copy-value"><?= $model->protocol . '://' . $model->alias; ?></div>
                            <a href="#" class="copy__link js-copy-link">
                                <svg class="icon" width="20px" height="20px">
                                    <use xlink:href="#icon-copy"></use>
                                </svg>
                            </a>
                        </div>
                    </li>
                    <li>
                        <b>Game url:</b>
                        <div class="copy">
                            <div
                                class="copy__value js-copy-value"><?= $model->protocol . '://' . $model->alias . '/gg/'; ?></div>
                            <a href="#" class="copy__link js-copy-link">
                                <svg class="icon" width="20px" height="20px">
                                    <use xlink:href="#icon-copy"></use>
                                </svg>
                            </a>
                        </div>
                    </li>
                <?php else : ?>
                    <li>
                        <b>Game url:</b>
                        <div class="copy">
                            <div
                                class="copy__value js-copy-value"><?= $model->protocol . '://' . $model->domain . '/gg/'; ?></div>
                            <a href="#" class="copy__link js-copy-link">
                                <svg class="icon" width="20px" height="20px">
                                    <use xlink:href="#icon-copy"></use>
                                </svg>
                            </a>
                        </div>
                    </li>
                <?php endif; ?>
                <li>
                    <b>Basic auth:</b>
                    <div class="copy">
                        <div class="copy__value js-copy-value">COMOA7aSYg</div>
                        <a href="#" class="copy__link js-copy-link">
                            <svg class="icon" width="20px" height="20px">
                                <use xlink:href="#icon-copy"></use>
                            </svg>
                        </a>
                    </div>
                </li>

            </ul>
        </div>
        <div class="info box">
            <div class="box__heading">
                <h2 class="box__title">Editor info</h2>
                <a href="#" class="copy__link js-copy-all-link">
                    Copy all
                    <svg class="icon" width="20px" height="20px">
                        <use xlink:href="#icon-copy"></use>
                    </svg>
                </a>
            </div>
            <div class="row">
                <div class="col-xs-6">
                    <?php if ($has_subdomains) : ?>
                        <?php foreach ($model->childs as $child) :
                            ?>
                            <p class="cred__title js-copy-title">FTP (<?= $child->domain; ?>)</p>
                            <ul class="cred">
                                <li>
                                    <b>Host:</b>
                                    <div class="copy">
                                        <div class="copy__value js-copy-value"><?php echo $child->server->ip ?></div>
                                        <a href="#" class="copy__link js-copy-link">
                                            <svg class="icon" width="20px" height="20px">
                                                <use xlink:href="#icon-copy"></use>
                                            </svg>
                                        </a>
                                    </div>
                                </li>
                                <li>
                                    <b>Login:</b>
                                    <div class="copy">
                                        <div
                                            class="copy__value js-copy-value"><?php echo $child->serverUser->editor_ftp_login ?></div>
                                        <a href="#" class="copy__link js-copy-link">
                                            <svg class="icon" width="20px" height="20px">
                                                <use xlink:href="#icon-copy"></use>
                                            </svg>
                                        </a>
                                    </div>
                                </li>
                                <li>
                                    <b>Password:</b>
                                    <div class="copy">
                                        <div
                                            class="copy__value js-copy-value"><?php echo $child->serverUser->editor_ftp_pass ?></div>
                                        <a href="#" class="copy__link js-copy-link">
                                            <svg class="icon" width="20px" height="20px">
                                                <use xlink:href="#icon-copy"></use>
                                            </svg>
                                        </a>
                                    </div>
                                </li>
                            </ul>
                        <?php endforeach; ?>
                    <?php else :
                        $ftp_login = $model->serverUser->ftp_login;
                        $ftp_pass = $model->serverUser->ftp_pass;

                        if (!empty($model->serverUser->editor_ftp_login)) {
                            $ftp_login = $model->serverUser->editor_ftp_login;
                        }

                        if (!empty($model->serverUser->editor_ftp_pass)) {
                            $ftp_pass = $model->serverUser->editor_ftp_pass;
                        }
                        ?>
                        <p class="cred__title js-copy-title">FTP (<?= $model->domain; ?>)</p>
                        <ul class="cred">
                            <li>
                                <b>Host:</b>
                                <div class="copy">
                                    <div class="copy__value js-copy-value"><?php echo $model->server->ip ?></div>
                                    <a href="#" class="copy__link js-copy-link">
                                        <svg class="icon" width="20px" height="20px">
                                            <use xlink:href="#icon-copy"></use>
                                        </svg>
                                    </a>
                                </div>
                            </li>
                            <li>
                                <b>Login:</b>
                                <div class="copy">
                                    <div
                                        class="copy__value js-copy-value"><?php echo $ftp_login; ?></div>
                                    <a href="#" class="copy__link js-copy-link">
                                        <svg class="icon" width="20px" height="20px">
                                            <use xlink:href="#icon-copy"></use>
                                        </svg>
                                    </a>
                                </div>
                            </li>
                            <li>
                                <b>Password:</b>
                                <div class="copy">
                                    <div
                                        class="copy__value js-copy-value"><?php echo $ftp_pass ?></div>
                                    <a href="#" class="copy__link js-copy-link">
                                        <svg class="icon" width="20px" height="20px">
                                            <use xlink:href="#icon-copy"></use>
                                        </svg>
                                    </a>
                                </div>
                            </li>
                        </ul>
                    <?php endif; ?>
                </div>

                <div class="col-sm-6">
                    <p class="cred__title js-copy-title">Admin credentials</p>
                    <ul class="cred">
                        <li>
                            <b>Url:</b>
                            <div class="copy">
								<a href="#" class="copy__link js-copy-link">
									<svg class="icon" width="20px" height="20px">
										<use xlink:href="#icon-copy"></use>
									</svg>
								</a>
                                <div class="copy__value js-copy-value">
                                    <?php echo ($model->alias) ? $model->alias . $model->admin_link : $model->domain . $model->admin_link; ?>
                                </div>
                            </div>
                        </li>
                        <li>
                            <b>Login:</b>
                            <div class="copy">
                                <div
                                    class="copy__value js-copy-value"><?php echo \Yii::$app->user->identity->admin_username; ?></div>
                                <a href="#" class="copy__link js-copy-link">
                                    <svg class="icon" width="20px" height="20px">
                                        <use xlink:href="#icon-copy"></use>
                                    </svg>
                                </a>
                            </div>
                        </li>
                        <li>
                            <b>Password:</b>
                            <div class="copy">
                                <div
                                    class="copy__value js-copy-value"><?php echo \Yii::$app->user->identity->admin_password; ?></div>
                                <a href="#" class="copy__link js-copy-link">
                                    <svg class="icon" width="20px" height="20px">
                                        <use xlink:href="#icon-copy"></use>
                                    </svg>
                                </a>
                            </div>
                        </li>

                    </ul>
                </div>

            </div>

        </div>

        <?php if ($has_subdomains) : ?>
            <div class="credentials box">
                <div class="row">
                    <div class="col-sm-6">
                        <p class="cred__title">FTP credentials</p>
                        <ul class="cred">
                            <li>
                                <b>Host:</b>
                                <div class="copy">
                                    <div class="copy__value js-copy-value"><?php echo $model->server->ip ?></div>
                                    <a href="#" class="copy__link js-copy-link">
                                        <svg class="icon" width="20px" height="20px">
                                            <use xlink:href="#icon-copy"></use>
                                        </svg>
                                    </a>
                                </div>
                            </li>
                            <li>
                                <b>Login:</b>
                                <div class="copy">
                                    <div
                                        class="copy__value js-copy-value"><?php echo $model->serverUser->ftp_login ?></div>
                                    <a href="#" class="copy__link js-copy-link">
                                        <svg class="icon" width="20px" height="20px">
                                            <use xlink:href="#icon-copy"></use>
                                        </svg>
                                    </a>
                                </div>
                            </li>
                            <li>
                                <b>Password:</b>
                                <div class="copy">
                                    <div
                                        class="copy__value js-copy-value"><?php echo $model->serverUser->ftp_pass ?></div>
                                    <a href="#" class="copy__link js-copy-link">
                                        <svg class="icon" width="20px" height="20px">
                                            <use xlink:href="#icon-copy"></use>
                                        </svg>
                                    </a>
                                </div>
                            </li>
                        </ul>
                    </div>

                </div>

            </div>
        <?php endif; ?>

        <div class="admin-info box">
            <h2 class="box__title">Administrator info</h2>

            <div class="row">
                <div class="col-sm-6">
                    <p>
                        <b><?= Yii::t('frontend', 'Panel link') ?>:</b>
                        <?= Html::a($model->server->link, $model->server->link, ['target' => '_blank']); ?>
                    </p>

                    <p>
                        <b>Root creditnails:</b><br>
                        <?php echo nl2br($model->server->credentials); ?>
                    </p>
                    <p>
                        <b><?= Yii::t('frontend', 'Server') ?>:</b>
                        <?= Html::a($model->server->title, ['server/view', 'id' => $model->server->id]) ?>
                    </p>
                    <p>
                        <b><?= Yii::t('frontend', 'Users') ?>:</b>
                        <?= Html::a($model->serverUser->title, ['server-user/view', 'id' => $model->serverUser->id]) ?>
                    </p>
                    <p>
                        <b><?= Yii::t('frontend', 'Template') ?>:
                        </b> <?= $model->template->title ?>
                    </p>
                </div>
                <div class="col-sm-6">

                    <p class="cred__title">FTP for admin</p>
                    <ul class="cred">
                        <li>
                            <b>Host:</b>
                            <div class="copy">
                                <div class="copy__value js-copy-value"><?php echo $model->server->ip ?></div>
                                <a href="#" class="copy__link js-copy-link">
                                    <svg class="icon" width="20px" height="20px">
                                        <use xlink:href="#icon-copy"></use>
                                    </svg>
                                </a>
                            </div>
                        </li>
                        <li>
                            <b>Login:</b>
                            <div class="copy">
                                <div
                                    class="copy__value js-copy-value"><?php echo $model->serverUser->ftp_login; ?></div>
                                <a href="#" class="copy__link js-copy-link">
                                    <svg class="icon" width="20px" height="20px">
                                        <use xlink:href="#icon-copy"></use>
                                    </svg>
                                </a>
                            </div>
                        </li>
                        <li>
                            <b>Password:</b>
                            <div class="copy">
                                <div
                                    class="copy__value js-copy-value"><?php echo $model->serverUser->ftp_pass; ?></div>
                                <a href="#" class="copy__link js-copy-link">
                                    <svg class="icon" width="20px" height="20px">
                                        <use xlink:href="#icon-copy"></use>
                                    </svg>
                                </a>
                            </div>
                        </li>
                    </ul>
                </div>
            </div>

        </div>

        <div class="box">
            <h2 class="box__title">Comments</h2>
            <?= $model->content ?>
        </div>

    </div>

    <div class="col-sm-3 sidebar">
        <div class="widget">
            <p class="widget__title">Сайты на этом сервере</p>

            <?php foreach ($model->findByServerId($model->server_id) as $item) : ?>
                <?= Html::a($item->domain, ['item/view', 'id' => $item->id]) ?>
            <?php endforeach; ?>
        </div>

        <div class="widget">
            <p class="widget__title">Назначеные редакторы</p>

            <?php foreach ($acceptedUsers as $acceptedUser) : ?>
                <div class="accepted-user">
                    <p class="accepted-user__title"><?= $acceptedUser->title; ?></p>
                    <div class="accepted-user__helper">
                        Добавьте в админку сайта пользователя
                        <p><b>Login: </b> <?= $acceptedUser->admin_username; ?></p>
                        <p><b>Password: </b> <?= $acceptedUser->admin_password; ?></p>
                    </div>
                </div>
            <?php endforeach; ?>

            <?= $this->render('./_formAddUser', [
                'model' => $userItemRelation,
                'users' => $users,
                'item_id' => $model->id,
            ]) ?>
        </div>

    </div>
</div>
