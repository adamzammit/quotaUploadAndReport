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
<?php
App()->clientScript->registerCssFile($assetUrl . '/jquery.jqplot.css');
App()->clientScript->registerCssFile($assetUrl . '/statistics.css');
App()->clientScript->registerScriptFile($jqplotUrl . '/jquery.jqplot.min.js');
App()->clientScript->registerScriptFile($jqplotUrl . '/plugins/jqplot.barRenderer.js');
App()->clientScript->registerScriptFile($jqplotUrl . '/plugins/jqplot.canvasTextRenderer.js');
App()->clientScript->registerScriptFile($jqplotUrl . '/plugins/jqplot.dateAxisRenderer.js');
App()->clientScript->registerScriptFile($jqplotUrl . '/plugins/jqplot.BezierCurveRenderer.js');
App()->clientScript->registerScriptFile($jqplotUrl . '/plugins/jqplot.categoryAxisRenderer.js');
App()->clientScript->registerScriptFile($jqplotUrl . '/plugins/jqplot.canvasAxisLabelRenderer.js');
App()->clientScript->registerScriptFile($jqplotUrl . '/plugins/jqplot.canvasAxisTickRenderer.js');
App()->clientScript->registerScriptFile($jqplotUrl . '/plugins/jqplot.pointLabels.js');

Yii::app()->getController()->renderPartial("{$className}.views.{$subview}",$_data_);
?>
