<?php
/**
 * Global layout for specific part for admin
 *
 * @author Denis Chenu <denis@sondages.pro>
 * @copyright 2016 Denis Chenu <http://www.sondages.pro>
 * @license GPL v3
 * @version 0.0.1
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 */
?>
<!DOCTYPE html>
<html lang="<?php echo App()->getLanguage(); ?>">
  <head>
    <!-- Meta, title, CSS, favicons, etc. -->
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?php echo flattenText($titre); ?></title>
    <?php
        Yii::app()->clientScript->registerCssFile($assetUrl . '/jquery.jqplot.css');
        Yii::app()->clientScript->registerCssFile($assetUrl . '/statistics.css');
        Yii::app()->clientScript->registerScriptFile($jqplotUrl . '/jquery.jqplot.min.js');
        Yii::app()->clientScript->registerScriptFile($jqplotUrl . '/plugins/jqplot.barRenderer.js');
        Yii::app()->clientScript->registerScriptFile($jqplotUrl . '/plugins/jqplot.canvasTextRenderer.js');
        Yii::app()->clientScript->registerScriptFile($jqplotUrl . '/plugins/jqplot.dateAxisRenderer.js');
        Yii::app()->clientScript->registerScriptFile($jqplotUrl . '/plugins/jqplot.BezierCurveRenderer.js');
        Yii::app()->clientScript->registerScriptFile($jqplotUrl . '/plugins/jqplot.categoryAxisRenderer.js');
        Yii::app()->clientScript->registerScriptFile($jqplotUrl . '/plugins/jqplot.canvasAxisLabelRenderer.js');
        Yii::app()->clientScript->registerScriptFile($jqplotUrl . '/plugins/jqplot.canvasAxisTickRenderer.js');

        Yii::app()->clientScript->registerScriptFile($jqplotUrl . '/plugins/jqplot.pointLabels.js');


    ?>
  </head>
 <body>
    <nav class="navbar navbar-default navbar-top">
        <div class="container">
           <div class="navbar-header">
              <button type="button" class="navbar-toggle collapsed" data-toggle="collapse" data-target="#navbar" aria-expanded="false" aria-controls="navbar">
                <span class="sr-only"><?php \Yii::t('',"Toggle navigation",array(),$className); ?></span>
                <span class="icon-bar"></span>
                <span class="icon-bar"></span>
                <span class="icon-bar"></span>
              </button>
                <?php echo CHtml::link(
                    CHtml::tag("span",array("class"=>''),Yii::app()->getConfig("sitename")),
                    array("plugins/direct","plugin"=>$className,"function"=>"list"),
                    array("class"=>'navbar-brand')
                ); ?>
            </div>
            <div id="navbar" class="navbar-collapse collapse">
              <ul class="nav navbar-nav navbar-right">
                <li><a href="http://extensions.sondages.pro/">About</a><li>
                <?php
                echo CHtml::tag('li',array('class'=>"dropdown"),"",false);
                echo CHtml::tag('a',array('class'=>"dropdown-toggle",'aria-expanded'=>'false','aria-haspopup'=>'true','role'=>'button','data-toggle'=>'dropdown'),Yii::app()->user->getName().' <b class="caret"></b>');
                echo CHtml::tag('ul',array('class'=>"dropdown-menu"),"",false);
                if($showAdmin) {
                    echo CHtml::tag('li',array(),
                        CHtml::link(gt("Administration"),array('/admin/index'))
                        );
                }
                echo CHtml::tag('li',array(),
                    CHtml::link(gt("Logout"),array('/admin/authentication','sa' => 'logout'))
                    );
                echo CHtml::closeTag('ul');
                echo CHtml::closeTag('li');
                ?>
            </div><!--/.nav-collapse -->
        </div>
    </nav>
    <div class="container">
      <?php
        Yii::app()->getController()->renderPartial("{$className}.views.{$subview}",$_data_);
      ?>
    </div> <!-- /container -->
</body>
