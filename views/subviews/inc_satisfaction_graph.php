    <h3 class="text-info"><?php echo $aResponse['title'] ?></h3>
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
            seriesColors:[quickstatpanel.seriecolor],
            seriesDefaults:{
                renderer:$.jqplot.BarRenderer,
                pointLabels: {
                  show: true,
                  location: 's',
                  formatString: "%#.2f",
                  color : quickstatpanel.labelcolor
                }
            },
            grid:{
              background : quickstatpanel.gridcolor
            },
            axes: {
                xaxis: {
                    renderer: $.jqplot.CategoryAxisRenderer,
                    ticks: ticks,
                    tickOptions: {
                        angle: <?php echo $angle; ?>,
                        textColor: quickstatpanel.ticktexcolor
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
