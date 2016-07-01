<h1><?php echo $titre; ?></h1>
<ul class="nav nav-tabs">
  <li role="presentation"><?php echo CHtml::link(gT("Participation"),array("plugins/direct","plugin"=>"adminStats","function"=>"participation","sid"=>$oSurvey->sid)); ?></li>
  <?php if($showSatisfaction) { ?>
  <li role="presentation" class="active"><a href="#"><?php echo gT("Satisfaction") ?></a></li>
  <?php } ?>
  <?php if($showAdminSurvey) { ?>
  <li role="presentation"><?php echo CHtml::link(gT("Administration"),array("admin/survey","sa"=>"editsurveysettings","surveyid"=>$oSurvey->sid,'#'=>'pluginsettings')); ?></a></li>
  <?php } ?>
</ul>
<?php /* inverse data */
  $aReorederSatisfactions=array();
  foreach($aResponses as $repKey=>$aResponse){
    foreach($aResponse['aSatisfactions'] as $iSatId=>$aSatisfaction)
    {
      if(empty($aReorederSatisfactions[$iSatId]))
      {
        $aReorederSatisfactions[$iSatId]=array(
          'title'=>$aSatisfaction['title'],
          'aResponses'=>array()
        );
      }
      $aReorederSatisfactions[$iSatId]['aResponses'][$repKey]=$aResponses[$repKey]['aSatisfactions'][$iSatId];
      $aReorederSatisfactions[$iSatId]['aResponses'][$repKey]['title']=$aResponses[$repKey]['title'];
    }
  }
?>

<?php foreach($aReorederSatisfactions as $iSatId=>$aSatisfaction){ ?>
  <h2><?php echo $aSatisfaction['title'] ?></h2>
  <?php foreach($aSatisfaction['aResponses'] as $repKey=>$aResponse){ ?>
    <h3><?php echo $aResponse['title'] ?></h2>
    <?php
      echo CHtml::tag("div",array("id"=>"chart-r{$repKey}-s{$iSatId}",'class'=>'graph'),"",true);
    ?>
    <?php
      $aLabels=array();
      $aAverage=array();
      foreach($aResponse['datas'] as $aDatas) {
        $aLabels[]="{$aDatas['title']} ({$aDatas['count']})";
        $aAverage[]=$aDatas['average'];

      } ?>
      <?php $angle=(count($aLabels)>6 ? -15:0);  // Evaluat number of columns ?>
      <script>
        $(document).ready(function(){
          $.jqplot.config.enablePlugins = true;
        var s1 = <?php echo json_encode($aAverage); ?>;
        var ticks = <?php echo json_encode($aLabels); ?>;

        <?php echo "plotr{$repKey}s{$iSatId}" ?> = $.jqplot('<?php echo "chart-r{$repKey}-s{$iSatId}"; ?>', [s1], {
            animate: false,
            seriesColors:['#0092dd'],
            seriesDefaults:{
                renderer:$.jqplot.BarRenderer,
                pointLabels: {
                  show: true,
                  location: 's',
                  formatString: "%#.2f",
                  color:'#ffffff'
                }
            },
            grid:{
              background : '#ffffff'
            },
            axes: {
                xaxis: {
                    renderer: $.jqplot.CategoryAxisRenderer,
                    ticks: ticks,
                    tickOptions: {
                        angle: <?php echo $angle; ?>,
                        textColor: '#000'
                    },
                    labelRenderer: $.jqplot.CanvasAxisLabelRenderer,
                    tickRenderer: $.jqplot.CanvasAxisTickRenderer
                },
                yaxis:{
                  min:<?php echo $aResponse['min']; ?>,
                  max:<?php echo $aResponse['max']; ?>
                }
            },
            highlighter: { show: false }
        });
    });
    </script>
  <?php } ?>
<?php } ?>



