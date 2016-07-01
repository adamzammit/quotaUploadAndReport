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
<html lang="en">
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

    <div class="navbar navbar-top">
      <div class="navbar-inner">
        <div class="container">
          <button type="button" class="btn btn-navbar" data-toggle="collapse" data-target=".nav-collapse">
            <span class="icon-bar"></span>
            <span class="icon-bar"></span>
            <span class="icon-bar"></span>
          </button>
          <div class="brand" href="#"><?php echo CHtml::link(
                CHtml::image("{$assetUrl}/logo.png").
                CHtml::tag("div",array("class"=>'sr-only'),Yii::app()->getConfig("sitename")),
                array("plugins/direct","plugin"=>"adminStats","function"=>"list")
            ); ?>
          </div>
          <div class="nav-collapse collapse">
            <ul class="nav pull-right">
                <?php

                if(!empty($surveyList))
                {
                    echo CHtml::tag('li',array(),
                        CHtml::link(gt("Surveys"),array('plugins/direct',"plugin"=>"adminStats","function"=>"list"))
                    );
                    //~ echo CHtml::tag('li',array('class'=>"dropdown"),"",false);
                    //~ echo CHtml::tag('a',array('class'=>"dropdown-toggle",'aria-expanded'=>'false','aria-haspopup'=>'true','role'=>'button','data-toggle'=>'dropdown'),gT("Surveys").' <b class="caret"></b>');
                    //~ echo CHtml::tag('ul',array('class'=>"dropdown-menu"),"",false);
                    //~ foreach($surveyList as $survey)
                    //~ {
                        //~ echo CHtml::tag('li',array(),
                            //~ CHtml::link($survey["surveyls_title"],array("plugins/direct","plugin"=>"adminStats","function"=>"stat","sid"=>$survey["sid"]))
                        //~ );
                    //~ }
                    //~ echo CHtml::closeTag('ul');
                    //~ echo CHtml::closeTag('li');
                }
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
            </ul>
          </div><!--/.nav-collapse -->
        </div>
      </div>
    </div>

    <div class="container">
      <?php
        Yii::app()->getController()->renderPartial("adminStats.views.{$subview}",$_data_);
      ?>
    </div> <!-- /container -->
</body>
