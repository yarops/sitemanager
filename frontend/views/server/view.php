<?php
/**
 * Created by PhpStorm.
 * User: georgy
 * Date: 18.10.14
 * Time: 2:14
 */
use yii\helpers\Html;
use yii\widgets\LinkPager;

/** @var $this yii\web\View */
/** @var $server \common\models\Server текущая категория */
/** @var $servers \yii\data\ActiveDataProvider список категорий */
/** @var $items \yii\data\ActiveDataProvider список категорий */

$this->title                   = Yii::t( 'frontend', 'Server' ) . ' ' . $server->title;
$this->params['breadcrumbs'][] = $this->title;
?>

<div class="col-sm-8 item-index">

	<form action="" method="$_GET">
		<label>
			<input type="checkbox" id="http" name="http" <?php echo ( Yii::$app->request->get( 'http' ) == 'on' ) ? 'checked' : ''; ?>>
			Only http
		</label>
		<button>Filter</button>
		<?php echo Html::a( 'Clear', array( 'item/index' ) ); ?>
	</form>


	<h1><?php echo Html::encode( $this->title ); ?></h1>

	<div>
		Sites: <?php echo $items->getTotalCount(); ?>
	</div>

	<?php
	foreach ( $items->models as $item ) {
		echo $this->render(
			'//item/shortView',
			array(
				'model' => $item,
			)
		);
	}
	?>
	<div>
		<?php
		echo LinkPager::widget(
			array(
				'pagination' => $items->getPagination(),
			)
		)
		?>
	</div>

</div>

<div class="col-sm-3 col-sm-offset-1 blog-sidebar">
	<h1><?php echo Yii::t( 'frontend', 'Servers' ); ?></h1>
	<ul>
		<?php
		foreach ( $servers->models as $server ) {
			echo $this->render(
				'shortViewServer',
				array(
					'model' => $server,
				)
			);
		}
		?>
	</ul>
</div>
