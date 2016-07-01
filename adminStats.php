<?php
/**
 * The plugin event redirect system
 *
 * @author Denis Chenu <denis@sondages.pro>
 * @copyright 2016 Denis Chenu <http://www.sondages.pro>
 * @copyright 2016 Advantage <http://www.advantage.fr>

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
class adminStats extends \ls\pluginmanager\PluginBase
{
    protected $storage = 'DbStorage';

    static protected $description = 'Do the statitics at the Advantage way';
    static protected $name = 'adminStats';

    private $aPushTokenValue=array(
        'Autre',
        'Autres',
        'autre',
        'autres',
        'Other',
        'Others',
        'other',
        'other',
    );
    private $aRenderData = array();
    protected $settings = array(
        'docu'=>array(
            'type' => 'info',
            'content' => '',
        ),
    );

    public function init()
    {

        $this->subscribe('beforeControllerAction');

        //~ $this->subscribe('afterSuccessfulLogin');

        $this->subscribe('beforeSurveySettings');
        $this->subscribe('newSurveySettings');

        $this->subscribe('newDirectRequest');

    }

    public function beforeSurveySettings()
    {
        $oEvent = $this->event;
        $aSettings=array();
        $oSurvey=Survey::model()->findByPk($oEvent->get('survey'));

        $aSettings=array();

        $url=$this->api->createUrl('plugins/direct', array('plugin' => $this->getName(), 'function' => 'stat',"sid"=>$oEvent->get('survey')));
        if(tableExists("{{survey_{$oSurvey->sid}}}"))
        {
            $aSettings["statlink"]=array(
                'type'=>'info',
                'content'=>"<h5 class='alert alert-info'>Lien vers les statistiques : <a href='{$url}'>{$url}</a></h5>",
            );
        }
        else
        {
            $aSettings["statlink"]=array(
                'type'=>'info',
                'content'=>"<p class='alert alert-info'>Survey is not activated : no statistics can be shown.</p>",
            );
        }
        $aSettings["alternateTitle"]=array(
            'type'=>'string',
            'label'=>"Titre alternative",
            'current'=>$this->get("alternateTitle","Survey",$oEvent->get('survey'),""),
        );
        $aSettings["numberMax"]=array(
            'type'=>'int',
            'label'=>"Nombre d'envoi global (sans invitations)",
            'htmlOptions'=>array(
                'min'=>0,
            ),
            'current'=>$this->get("numberMax","Survey",$oEvent->get('survey'),0),
        );

        $aSettings["CrossTitle"]=array(
            'type'=>'info',
            'content'=>"<h5 class='alert alert-info'>Onglet participation</h5>"
        );
        if($oSurvey->datestamp=="Y"){
            $aSettings["dailyRate"]=array(
                'type'=>'select',
                'label'=>"Montrer le taux de réponse finalisé journalier",
                'options'=>array(
                    '1'=>gT('Yes'),
                    '0'=>gT('No'),
                ),
                'current'=>$this->get("dailyRate","Survey",$oEvent->get('survey'),1),
            );
            $aSettings["dailyRateEnter"]=array(
                'type'=>'select',
                'label'=>"Montrer le taux d'entrée journalier",
                'options'=>array(
                    '1'=>gT('Yes'),
                    '0'=>gT('No'),
                ),
                'current'=>$this->get("dailyRateEnter","Survey",$oEvent->get('survey'),0),
            );
            //~ $aSettings["dailyRateAction"]=array(
                //~ 'type'=>'select',
                //~ 'label'=>"Montrer le taux d'action journalier",
                //~ 'options'=>array(
                    //~ '1'=>gT('Yes'),
                    //~ '0'=>gT('No'),
                //~ ),
                //~ 'current'=>$this->get("dailyRateAction","Survey",$oEvent->get('survey'),0),
            //~ );
        }else{
            $aSettings["dailyRate"]=array(
                'type'=>'info',
                'label'=>"Le questionnaire n'est pas daté: impossible de montrer les taux journalier",
            );
        }
        /* Token attribute */
        if(tableExists("{{tokens_{$oEvent->get('survey')}}}")){
            $aRealTokenAttributes=array_keys(Yii::app()->db->schema->getTable("{{tokens_{$oEvent->get('survey')}}}")->columns);
            $aRealTokenAttributes=array_combine($aRealTokenAttributes,$aRealTokenAttributes);
            $aTokenAttributes=array_filter(Token::model($oEvent->get('survey'))->attributeLabels());
            $aTokenAttributes=array_diff_key(
                array_replace($aRealTokenAttributes,$aTokenAttributes),
                array(
                      'tid' => 'tid',
                      'partcipant' => 'partcipant',
                      'participant' => 'participant',
                      'participant_id' => 'participant_id',
                      'firstname' => 'firstname',
                      'lastname' => 'lastname',
                      'email' => 'email',
                      'emailstatus' => 'emailstatus',
                      'token' => 'token',
                      'language' => 'language',
                      'blacklisted' => 'blacklisted',
                      'sent' => 'sent',
                      'remindersent' => 'remindersent',
                      'remindercount' => 'remindercount',
                      'completed' => 'completed',
                      'usesleft' => 'usesleft',
                      'validfrom' => 'validfrom',
                      'validuntil' => 'validuntil',
                      'mpid' => 'mpid'
                )
            );
            if(!empty($aTokenAttributes))
            {
                $aOptions=array();
                foreach($aTokenAttributes as $attribute=>$description)
                {
                    $aOptions[$attribute]=(empty($description) ? $attribute : $description);
                }
                $aSettings["tokenAttributes"]=array(
                    'type'=>'select',
                    'label'=>"Attribut d'invitation pour les croisements",
                    'options'=>$aOptions,
                    'htmlOptions'=>array(
                        'multiple'=>'multiple',
                    ),
                    'current'=>$this->get("tokenAttributes","Survey",$oEvent->get('survey')),
                );
            }
        }

        /* Single choice question */
        $oCriteria=new CdbCriteria();
        $oCriteria->condition="t.sid=:sid and t.language=:language";
        $oCriteria->params[':sid'] = $oSurvey->sid;
        $oCriteria->params[':language'] = $oSurvey->language;
        $oCriteria->addInCondition("type",array("L","!")); // see "*"
        $oCriteria->order='group_order ASC, question_order ASC';
        $aoSingleQuestion=Question::model()->with('groups')->findAll($oCriteria);
        if(!empty($aoSingleQuestion))
        {
            $aSettings["questionCross"]=array(
                'type'=>'select',
                'label'=>"Question pour les croisements",
                'options'=>CHtml::listData($aoSingleQuestion,'qid',function($oSingleQuestion) {return "[".$oSingleQuestion->title."] ".viewHelper::flatEllipsizeText($oSingleQuestion->question,1,80,"...",0.6);}),
                'htmlOptions'=>array(
                    'multiple'=>'multiple',
                ),
                'current'=>$this->get("questionCross","Survey",$oEvent->get('survey')),
            );
        }
        /* numeric question */
        $oCriteria=new CdbCriteria();
        $oCriteria->condition="parent_qid=0 and t.sid=:sid and t.language=:language";
        $oCriteria->params[':sid'] = $oSurvey->sid;
        $oCriteria->params[':language'] = $oSurvey->language;
        $oCriteria->addInCondition("type",array("L","!","F","N","K","A","B",";")); // see "*"
        $oCriteria->order='group_order ASC, question_order ASC';
        $aoNumericPossibleQuestion=Question::model()->with('groups')->findAll($oCriteria);
        $aQuestionNumeric=array();
        foreach($aoNumericPossibleQuestion as $oQuestion)
        {

            switch($oQuestion->type)
            {
                case "L":
                case "!":
                    // @todo : Test if have answer numeric
                    $iNumAnswers=Answer::model()->count("qid=:qid AND concat('',code * 1) = code",array(":qid"=>$oQuestion->qid));
                    if($iNumAnswers)
                    {
                        $aQuestionNumeric["{$oQuestion->qid}"]="[{$oQuestion->title}] ".viewHelper::flatEllipsizeText($oQuestion->question,1,80,"...",0.6);
                    }
                    break;
                case "N":
                    $aQuestionNumeric["{$oQuestion->qid}"]="[{$oQuestion->title}] ".viewHelper::flatEllipsizeText($oQuestion->question,1,80,"...",0.6);
                    break;
                case "K":
                    $oSubQuestions=Question::model()->findAll(array("condition"=>"parent_qid=:qid AND language=:language","order"=>"question_order","params"=>array(":qid"=>$oQuestion->qid,":language"=>$oSurvey->language)));
                    foreach($oSubQuestions as $oSubQuestion)
                    {
                        $aQuestionNumeric["{$oSubQuestion->qid}"]="[{$oQuestion->title}_{$oSubQuestion->title}] ".viewHelper::flatEllipsizeText($oQuestion->question,1,40,"...",0.6)." : ".viewHelper::flatEllipsizeText($oSubQuestion->question,1,40,"...",0.6);
                    }
                    break;
                case "A":
                case "B":
                    $oSubQuestions=Question::model()->findAll(array("condition"=>"qid=:qid AND language=:language","order"=>"question_order","params"=>array(":qid"=>$oQuestion->qid,":language"=>$oSurvey->language)));
                    foreach($oSubQuestions as $oSubQuestion)
                    {
                        $aQuestionNumeric["{$oSubQuestion->qid}"]="[{$oQuestion->title}_{$oSubQuestion->title}] ".viewHelper::flatEllipsizeText($oQuestion->question,1,40,"...",0.6)." : ".viewHelper::flatEllipsizeText($oSubQuestion->question,1,40,"...",0.6);
                    }
                    break;
                case "F":
                    $iNumAnswers=Answer::model()->count("qid=:qid AND concat('',code * 1) = code",array(":qid"=>$oQuestion->qid));
                    if($iNumAnswers)
                    {
                        $oSubQuestions=Question::model()->findAll(array("condition"=>"parent_qid=:qid AND language=:language","order"=>"question_order","params"=>array(":qid"=>$oQuestion->qid,":language"=>$oSurvey->language)));
                        foreach($oSubQuestions as $oSubQuestion)
                        {
                            $aQuestionNumeric["{$oSubQuestion->qid}"]="[{$oQuestion->title}_{$oSubQuestion->title}] ".viewHelper::flatEllipsizeText($oQuestion->question,1,40,"...",0.6)." : ".viewHelper::flatEllipsizeText($oSubQuestion->question,1,40,"...",0.6);
                        }
                    }
                    break;
                case ";":
                    // Find if have starRating system
                    $aoSubQuestionX=Question::model()->findAll(array(
                        'condition'=>"parent_qid=:parent_qid and language=:language and scale_id=:scale_id",
                        'params'=>array(":parent_qid"=>$oQuestion->qid,":language"=>App()->language,":scale_id"=>1),
                        'index'=>'qid',
                    ));
                    $oCriteria = new CDbCriteria;
                    $oCriteria->condition="attribute='arrayTextAdaptation'";
                    $oCriteria->addSearchCondition('value','star%',false);
                    $oCriteria->addInCondition("qid",CHtml::listData($aoSubQuestionX,'qid','qid'));
                    $iExistingAttribute=QuestionAttribute::model()->count($oCriteria);
                    if($iExistingAttribute)
                    {
                        $oSubQuestions=Question::model()->findAll(array("condition"=>"parent_qid=:qid AND language=:language AND scale_id=:scale_id","order"=>"question_order","params"=>array(":qid"=>$oQuestion->qid,":language"=>$oSurvey->language,":scale_id"=>0)));
                        foreach($oSubQuestions as $oSubQuestion)
                        {
                            $aQuestionNumeric["{$oSubQuestion->qid}"]="[{$oQuestion->title}_{$oSubQuestion->title}] ".viewHelper::flatEllipsizeText($oQuestion->question,1,40,"...",0.6)." : ".viewHelper::flatEllipsizeText($oSubQuestion->question,1,40,"...",0.6);
                        }
                    }
                    break;
                default:
                    break;
            }
        }
        if(!empty($aQuestionNumeric))
        {
            $aSettings["SatTitle"]=array(
                'type'=>'info',
                'content'=>"<h5 class='alert alert-info'>Onglet satisfaction</h5>"
            );
            $aSettings["questionNumeric"]=array(
                'type'=>'select',
                'label'=>"Question pour les moyennes",
                'options'=>$aQuestionNumeric,
                'htmlOptions'=>array(
                    'multiple'=>'multiple',
                ),
                'current'=>$this->get("questionNumeric","Survey",$oEvent->get('survey')),
            );
            if(!empty($aTokenAttributes))
            {
                $aOptions=array();
                foreach($aTokenAttributes as $attribute=>$description)
                {
                    $aOptions[$attribute]=(empty($description) ? $attribute : $description);
                }
                $aSettings["tokenAttributesSatisfaction"]=array(
                    'type'=>'select',
                    'label'=>"Attribut d'invitation pour les croisements de satisfaction",
                    'options'=>$aOptions,
                    'htmlOptions'=>array(
                        'multiple'=>'multiple',
                    ),
                    'current'=>$this->get("tokenAttributesSatisfaction","Survey",$oEvent->get('survey')),
                );
            }
            if(!empty($aoSingleQuestion))
            {
                $aSettings["questionCrossSatisfaction"]=array(
                    'type'=>'select',
                    'label'=>"Question pour les croisements de satisfaction",
                    'options'=>CHtml::listData($aoSingleQuestion,'qid',function($oSingleQuestion) {return "[".$oSingleQuestion->title."] ".viewHelper::flatEllipsizeText($oSingleQuestion->question,1,80,"...",0.6);}),
                    'htmlOptions'=>array(
                        'multiple'=>'multiple',
                    ),
                    'current'=>$this->get("questionCrossSatisfaction","Survey",$oEvent->get('survey')),
                );
            }
        }
        $oEvent->set("surveysettings.{$this->id}", array(
            'name' => get_class($this),
            'settings' => $aSettings,
        ));
    }
    public function newSurveySettings()
    {
        $event = $this->event;
        $aSettings=$event->get('settings');
        /* Fix not set dropdown */
        $aSettings['tokenAttributes'] = isset($aSettings['tokenAttributes']) ? $aSettings['tokenAttributes'] : null;
        $aSettings['questionCross'] = isset($aSettings['questionCross']) ? $aSettings['questionCross'] : null;
        $aSettings['questionNumeric'] = isset($aSettings['questionNumeric']) ? $aSettings['questionNumeric'] : null;
        $aSettings['tokenAttributesSatisfaction'] = isset($aSettings['tokenAttributesSatisfaction']) ? $aSettings['tokenAttributesSatisfaction'] : null;
        $aSettings['questionCrossSatisfaction'] = isset($aSettings['questionCrossSatisfaction']) ? $aSettings['questionCrossSatisfaction'] : null;

        foreach ($aSettings as $name => $value)
        {
            /* In order use survey setting, if not set, use global, if not set use default */
            $default=$event->get($name,null,null,isset($this->settings[$name]['default'])?$this->settings[$name]['default']:NULL);
            $this->set($name, $value, 'Survey', $event->get('survey'),$default);
        }
    }
    /**
     * Always redirect user to stat if don't have Global survey access
     */
    public function beforeControllerAction()
    {
        if($this->onlyStatAccess() && ($this->event->get('controller')=='admin' && $this->event->get('action')!='authentication'))
        {
            Yii::app()->controller->redirect(array('plugins/direct','plugin' => $this->getName(), 'function' => 'list'));
        }
    }

    /**
     * The request action test
     */
    public function newDirectRequest()
    {
        Yii::import('application.helpers.viewHelper');

        if ($this->event->get('target') != get_class())
            return;
        if(Yii::app()->user->getIsGuest())
        {
          App()->user->setReturnUrl(App()->request->requestUri);
          App()->controller->redirect(array('/admin/authentication'));
        }
        $sAction=$this->event->get('function');

        $this->iSurveyId=$this->api->getRequest()->getParam('sid');
        $oSurvey=Survey::model()->findByPK($this->iSurveyId);
        if($this->iSurveyId && !$oSurvey)
        {
            throw new CHttpException(404, gT("The survey does not seem to exist."));
        }
        elseif($this->iSurveyId)
        {
            if(!Permission::model()->hasSurveyPermission($this->iSurveyId,'statistics'))
            {
                throw new CHttpException(401,gT("You do not have sufficient rights to access this page."));
            }
            if(tableExists("{{survey_{$oSurvey->sid}}}"))
            {
                $oSurvey=Survey::model()->with('languagesettings')->find("sid=:sid",array(":sid"=>$this->iSurveyId));
                $this->aRenderData['titre']=$this->get("alternateTitle","Survey",$oSurvey->sid,"");
                if(empty($this->aRenderData['titre'])){
                    $this->aRenderData['titre']=$oSurvey->getLocalizedTitle();
                }
                $this->aRenderData['oSurvey']=$oSurvey;
                $sAction=in_array($sAction,array('participation','satisfaction','export')) ? $sAction : 'participation';
            }
            else
            {
                $sAction="list";
            }
        }
        else
        {
            $sAction=false;
        }
        switch ($sAction)
        {
            case "list":
                $this->actionList();
                break;
            case "participation":
                $this->actionParticipation();
                break;
            case "satisfaction":
                $this->actionSatisfaction();
                break;
            case "export":
                $this->actionExportData();
                break;
            default:
                $this->actionList();
                break;
        }
    }

    /**
     * Get participation for this survey
     * @return void (rendering)
     */
    public function actionParticipation()
    {
        if(empty($this->aRenderData['oSurvey']))
            throw new CHttpException(500);
        $oSurvey=$this->aRenderData['oSurvey'];
        if($oSurvey->datestamp=="Y")
        {
            if($this->get("dailyRate","Survey",$oSurvey->sid,1))
            {
                $this->aRenderData['aDailyResponses']=$this->getDailyResponsesRate($this->iSurveyId);
            }
            if($this->get("dailyRateEnter","Survey",$oSurvey->sid,0))
            {
                $this->aRenderData['aDailyEnter']=$this->getDailyResponsesRate($this->iSurveyId,'startdate');
            }
            if(false && $this->get("dailyRateAction","Survey",$oSurvey->sid,0))
            {
                $this->aRenderData['aDailyAction']=$this->getDailyResponsesRate($this->iSurveyId,'datestamp');
            }
        }
        $this->aRenderData['aResponses']=$this->getParticipationRate($this->iSurveyId);
        $this->render('participation');
    }

    protected function getParticipationRate($iSurveyId)
    {
        $oSurvey=Survey::model()->findByPk($iSurveyId);
        /* decompte */
        $aResponses=array();
        /* Total */
        if(tableExists("{{tokens_{$iSurveyId}}}"))
        {
            $max=Token::model($iSurveyId)->count();// see with Token::model($iSurveyId)->empty()->count()
        }else{
            $max=$this->get("numberMax","Survey",$iSurveyId,0);
            $max=($max>1) ? $max : 0;
        }
        $aResponses['total']=array(
            'title'=>gT("Population"),
            'max'=>$max,
            'data'=>array(
                array('title'=>gT("Population Totale"),'max'=>$max,'completed'=>Response::model($iSurveyId)->count("submitdate IS NOT NULL")),
            ),
        );
        /* by token */
        $aTokenCross=$this->get("tokenAttributes","Survey",$iSurveyId);
        if(!empty($aTokenCross) && tableExists("{{tokens_{$iSurveyId}}}"))
        {
            $aValidAttributes=Token::model($iSurveyId)->attributeLabels();
            foreach($aTokenCross as $tokenCross)
            {
                if(array_key_exists($tokenCross,$aValidAttributes))
                {
                    /* The list */
                    $aTokenValues=$this->getTokenValues($tokenCross);
                    $aData=array();
                    $globalMax=0;
                    foreach($aTokenValues as $sTokenValue)
                    {
                        $max=Token::model($iSurveyId)->count("$tokenCross=:tokenvalue",array(":tokenvalue"=>$sTokenValue));
                        $globalMax+=$max;
                        $aData[]=array(
                            'title'=>viewHelper::flatEllipsizeText($sTokenValue,true,false),
                            'max'=>$max,
                            'completed'=>Token::model($iSurveyId)->count("$tokenCross=:tokenvalue AND completed!='N' AND completed<>''",array(":tokenvalue"=>$sTokenValue)),
                        );
                    }
                    $aResponses[$tokenCross]=array(
                        'title'=>viewHelper::flatEllipsizeText($aValidAttributes[$tokenCross],true,false),
                        'max'=>$max,
                        'data'=>$aData,
                    );
                }
            }
        }
        /* by questions */
        $aQuestionsCross=$this->get("questionCross","Survey",$iSurveyId);
        if(!empty($aQuestionsCross))
        {
            $oCriteria=new CdbCriteria();
            $oCriteria->condition="t.sid=:sid and t.language=:language";
            $oCriteria->params[':sid'] = $oSurvey->sid;
            $oCriteria->params[':language'] = $oSurvey->language;
            $oCriteria->addInCondition("type",array("L","!"));
            $oCriteria->addInCondition("qid",$aQuestionsCross);
            $oCriteria->order='group_order ASC, question_order ASC';
            $aoSingleQuestion=Question::model()->with('groups')->findAll($oCriteria);
            if(!empty($aoSingleQuestion))
            {
                foreach($aoSingleQuestion as $oSingleQuestion)
                {
                    $sColumn="{$oSingleQuestion->sid}X{$oSingleQuestion->gid}X{$oSingleQuestion->qid}";
                    $aData=array();
                    $oAnswers=Answer::model()->findAll(array(
                        'condition'=>"qid=:qid and language=:language",
                        'order'=>"sortorder",
                        'params'=>array(":qid"=>$oSingleQuestion->qid,":language"=>$oSurvey->language)
                    ));
                    $globalMax=0;
                    foreach($oAnswers as $oAnswer)
                    {
                        $countCriteria=new CdbCriteria();
                        $countCriteria->condition="submitdate IS NOT NULL";
                        $countCriteria->compare(Yii::app()->db->quoteColumnName($sColumn),$oAnswer->code);
                        $globalMax+=($oAnswer->assessment_value>1 ? $oAnswer->assessment_value : 0);
                        $aData[]=array(
                            'title'=>viewHelper::flatEllipsizeText($oAnswer->answer,true,false),
                            'max'=>($oAnswer->assessment_value>1 ? $oAnswer->assessment_value : 0),
                            'completed'=>Response::model($iSurveyId)->count($countCriteria)
                        );
                    }
                    $aResponses[$sColumn]=array(
                        'title'=>viewHelper::flatEllipsizeText($oSingleQuestion->question,true,false),
                        'max'=>$globalMax,
                        'data'=>$aData,
                    );
                }
            }
        }
        return $aResponses;
    }
    /**
     * Show Satisfaction for this survey
     * @return void (rendering)
     */
    public function actionSatisfaction()
    {
        if(empty($this->aRenderData['oSurvey']))
            throw new CHttpException(500);
        $oSurvey=$this->aRenderData['oSurvey'];
        $aResponses=array();
        /* Global */
        $aQuestionsNumeric=$this->get("questionNumeric","Survey",$this->iSurveyId,array());
        $aData=array();
        $aDataInfos=array(); // Use some data for all datas : less easy than $aData['total'][$sColumn]
        foreach($aQuestionsNumeric as $iQuestionNumeric)
        {
            /* find the code column */
            $oQuestion=Question::model()->find("qid=:qid AND language=:language",array(":qid"=>$iQuestionNumeric,":language"=>$oSurvey->language));
            if($oQuestion)
            {
                $maxByQuestion=0;
                if($oQuestion->parent_qid)
                {
                    $oParentQuestion=Question::model()->find("qid=:qid AND language=:language",array(":qid"=>$oQuestion->parent_qid,":language"=>$oSurvey->language));
                    if($oParentQuestion->type==';')
                    {
                        $aoSubQuestionX=Question::model()->findAll(array(
                            'condition'=>"parent_qid=:parent_qid and language=:language and scale_id=:scale_id",
                            'params'=>array(":parent_qid"=>$oParentQuestion->qid,":language"=>App()->language,":scale_id"=>1),
                            'index'=>'qid',
                        ));
                        $oCriteria = new CDbCriteria;
                        $oCriteria->condition="attribute='arrayTextAdaptation'";
                        $oCriteria->addSearchCondition('value','star%',false);
                        $oCriteria->addInCondition("qid",CHtml::listData($aoSubQuestionX,'qid','qid'));
                        $oExistingAttribute=QuestionAttribute::model()->find($oCriteria);
                        if($oExistingAttribute)
                        {
                            $maxByQuestion=intval(substr($oExistingAttribute->value, 4));
                            $oXQuestion=Question::model()->find("qid=:qid AND language=:language",array(":qid"=>$oExistingAttribute->qid,":language"=>$oSurvey->language));
                            if($oXQuestion)
                            {
                                $sColumnName="{$oParentQuestion->sid}X{$oParentQuestion->gid}X{$oParentQuestion->qid}{$oQuestion->title}_{$oXQuestion->title}";
                            }
                        }
                    }
                    else
                    {
                        $sColumnName="{$oParentQuestion->sid}X{$oParentQuestion->gid}X{$oParentQuestion->qid}{$oQuestion->title}";
                        switch ($oParentQuestion->type)
                        {
                            case 'F':
                                $sQuotedColumn=Yii::app()->db->quoteColumnName('code');
                                $oCriteria = new CDbCriteria;
                                $oCriteria->condition="qid =:qid";
                                $oCriteria->addCondition("concat('',{$sQuotedColumn} * 1) = {$sQuotedColumn}");
                                $oCriteria->params[":qid"]=$oParentQuestion->qid;
                                $maxByQuestion = max(CHtml::listData(Answer::model()->findAll($oCriteria),'code','code'));
                                break;
                            case "A":
                                $maxByQuestion = 5;
                                break;
                            case "B":
                                $maxByQuestion = 10;
                                break;
                        }
                    }
                    $sTitle="<small>".viewHelper::flatEllipsizeText($oParentQuestion->question,true,false)."</small> \n".viewHelper::flatEllipsizeText($oQuestion->question,true,false);
                }
                else
                {
                    $sColumnName="{$oQuestion->sid}X{$oQuestion->gid}X{$oQuestion->qid}";
                    $sTitle=viewHelper::flatEllipsizeText($oQuestion->question,true,false);
                    if(in_array($oQuestion->type,array("L","!")))
                    {
                        $sQuotedColumn=Yii::app()->db->quoteColumnName('code');
                        $oCriteria = new CDbCriteria;
                        $oCriteria->condition="qid =:qid";
                        $oCriteria->addCondition("concat('',{$sQuotedColumn} * 1) = {$sQuotedColumn}");
                        $oCriteria->params[":qid"]=$oQuestion->qid;
                        $maxByQuestion = max(CHtml::listData(Answer::model()->findAll($oCriteria),'code','code'));
                    }
                    elseif($oQuestion->type=="N")
                    {

                    }

                }
                if(!empty($sColumnName))
                {
                    $iCount=$this->getCountNumeric($sColumnName);
                    if($iCount)
                    {
                        $aDataInfos[$sColumnName]=array(
                            'title'=>$sTitle,
                            'min'=>0,
                            'max'=>max($maxByQuestion,$this->getMax($sColumnName)),
                        );
                        $aData[$sColumnName]=array(
                            'title'=>$sTitle,
                            'min'=>0,
                            'max'=>max($maxByQuestion,$this->getMax($sColumnName)),
                            'datas'=>array(
                                array(
                                    'title'=>gT("Population Totale"),
                                    'count'=>$iCount,
                                    'average'=>$this->getAverage($sColumnName),
                                ),
                            ),
                        );
                    }
                }
            }
        }
        //tracevar($aDataInfos);
        if(!empty($aData))
        {
            $aResponses['total']=array(
                'title'=>gT("Population"),
                'aSatisfactions'=>$aData,
            );
        }
        /* Do it for each */
        $aTokenCross=$this->get("tokenAttributesSatisfaction","Survey",$this->iSurveyId,array());
        if(!empty($aDataInfos) && !empty($aTokenCross) && tableExists("{{tokens_{$this->iSurveyId}}}"))
        {
            $aValidAttributes=Token::model($this->iSurveyId)->attributeLabels();
            foreach($aTokenCross as $tokenCross)
            {
                if(array_key_exists($tokenCross,$aValidAttributes))
                {
                    $aTokenValues=$this->getTokenValues($tokenCross);

                    $aData=array();
                    foreach($aDataInfos as $sColumnName=>$aDataInfo)
                    {

                        /* Start by population */
                        //~ $aData=array(
                            //~ array(
                                //~ 'title'=>gT("Population"),
                                //~ 'count'=>$this->getCountNumeric($sColumnName,array($tokenCross=>$aTokenValues)),
                                //~ 'average'=>$this->getAverage($sColumnName,array($tokenCross=>$aTokenValues)),
                            //~ ),
                        //~ );
                        $aData=array();
                        foreach($aTokenValues as $sTokenValue)
                        {
                            $value=$sTokenValue;
                            $aData[]=array(
                                'title'=>viewHelper::flatEllipsizeText($sTokenValue,true,false),
                                'count'=>$this->getCountNumeric($sColumnName,array($tokenCross=>$sTokenValue)),
                                'average'=>$this->getAverage($sColumnName,array($tokenCross=>$sTokenValue)),
                            );
                        }
                        if(!empty($aData))
                        {
                            $aSatisfaction[$sColumnName]=array(
                                'title'=>$aDataInfos[$sColumnName]['title'],
                                'min'=>0,
                                'max'=>$aDataInfos[$sColumnName]['max'],
                                'datas'=>$aData,
                            );
                        }
                    }
                    $aResponses[$tokenCross]=array(
                        'title'=>viewHelper::flatEllipsizeText($aValidAttributes[$tokenCross],true,false),
                        'aSatisfactions'=>$aSatisfaction,
                    );
                }
            }


        }
        $aQuestionsCross=$this->get("questionCrossSatisfaction","Survey",$this->iSurveyId);
        if(!empty($aDataInfos) && !empty($aQuestionsCross))
        {
            $oCriteria=new CdbCriteria();
            $oCriteria->condition="t.sid=:sid and t.language=:language";
            $oCriteria->params[':sid'] = $oSurvey->sid;
            $oCriteria->params[':language'] = $oSurvey->language;
            $oCriteria->addInCondition("type",array("L","!"));
            $oCriteria->addInCondition("qid",$aQuestionsCross);
            $oCriteria->order='group_order ASC, question_order ASC';
            $aoSingleQuestion=Question::model()->with('groups')->findAll($oCriteria);
            if(!empty($aoSingleQuestion))
            {
                foreach($aoSingleQuestion as $oSingleQuestion)
                {
                    $sColumn="{$oSingleQuestion->sid}X{$oSingleQuestion->gid}X{$oSingleQuestion->qid}";
                    $oAnswers=Answer::model()->findAll(array(
                        'condition'=>"qid=:qid and language=:language",
                        'order'=>"sortorder",
                        'params'=>array(":qid"=>$oSingleQuestion->qid,":language"=>$oSurvey->language)
                    ));
                    $aAnswers=Chtml::listData($oAnswers,'code','answer');
                    $aData=array();
                    foreach($aDataInfos as $sColumnName=>$aDataInfo)
                    {
                        /* Start by population */
                        //~ $aData=array(
                            //~ array(
                                //~ 'title'=>gT("Population"),
                                //~ 'count'=>$this->getCountNumeric($sColumnName,array($sColumn=>array_keys($aAnswers))),
                                //~ 'average'=>$this->getAverage($sColumnName,array($sColumn=>array_keys($aAnswers))),
                            //~ ),
                        //~ );
                        $aData=array();
                        foreach($aAnswers as $sCode=>$sAnswer)
                        {
                            $aData[]=array(
                                'title'=>viewHelper::flatEllipsizeText($sAnswer,true,false),
                                'count'=>$this->getCountNumeric($sColumnName,array($sColumn=>$sCode)),
                                'average'=>$this->getAverage($sColumnName,array($sColumn=>$sCode)),
                            );
                        }
                        if(!empty($aData))
                        {
                            $aSatisfaction[$sColumnName]=array(
                                'title'=>$aDataInfos[$sColumnName]['title'],
                                'min'=>0,
                                'max'=>$aDataInfos[$sColumnName]['max'],
                                'datas'=>$aData,
                            );
                        }
                    }
                    $aResponses[$sColumn]=array(
                        'title'=>viewHelper::flatEllipsizeText($oSingleQuestion->question,true,false),
                        'aSatisfactions'=>$aSatisfaction,
                    );
                }
            }
        }
        $this->aRenderData['aResponses']=$aResponses;

        $this->render('satisfaction');
    }

    public function actionExportData()
    {
        if(empty($this->aRenderData['oSurvey']))
            throw new CHttpException(500);
        $oSurvey=$this->aRenderData['oSurvey'];
        $exportType="dayresponse";
        $type=App()->getRequest()->getParam('state');
        switch($type)
        {
            case 'enter':
                $state='startdate';
                break;
            case 'action':
                $state='datestamp';
                break;
            default:
                $state='submitdate';
        }
        $aDatas=$this->getDailyResponsesRate($oSurvey->sid,$state);
        $aHeader=array(gT("Day"),gT("Nb"));
        header("Content-Disposition: attachment; filename=" . $state.".csv");
        header("Content-type: text/comma-separated-values; charset=UTF-8");
        echo implode(",",$aHeader). PHP_EOL;
        foreach($aDatas as $key=>$value)
        {
            echo $key.",".$value. PHP_EOL;
        }
        die();
    }
    /**
     * Get the reponse by day
     * @param int iSurveyId : the id of the survey
     * @param string state : date to take into account
     * @return array (response by day)
     */
    public function getDailyResponsesRate($iSurveyId,$state='submitdate')
    {

        $aDailyResponsesRateArray=Yii::app()->db->createCommand()
            ->select("DATE({$state}) as ".Yii::app()->db->quoteColumnName("date").",COUNT(*) AS ".Yii::app()->db->quoteColumnName("nb"))
            ->from("{{survey_{$iSurveyId}}} s")
            ->where("{$state} IS NOT NULL")
            ->order("date")
            ->group("date")
            ->queryAll();
        $aDailyResponsesRate=array();
        foreach($aDailyResponsesRateArray as $aDailyResponse)
        {
            $aDailyResponsesRate[$aDailyResponse['date']]=$aDailyResponse['nb'];
        }
        return $aDailyResponsesRate;
    }
    /**
     * Get list of statictics survey for this user
     * @return void (rendering)
     */
    public function actionList()
    {
        $this->aRenderData['titre']=gt("Surveys");
        $aStatSurveys=$this->getSurveyList();
        $aFinalSurveys=array();
        foreach($aStatSurveys as $aStatSurvey)
        {
            $aStatSurvey['responseTotal']=Response::model($aStatSurvey['sid'])->count();
            $aStatSurvey['responsesCount']=Response::model($aStatSurvey['sid'])->count("submitdate IS NOT NULL");
            if(tableExists("{{tokens_{$aStatSurvey['sid']}}}")){
                $aStatSurvey['tokensCount']=Token::model($aStatSurvey['sid'])->count();
            }else{
                $aStatSurvey['tokensCount']=$this->get("numberMax","Survey",$aStatSurvey['sid'],0);
            }
            if(intval($aStatSurvey['responsesCount'])>0)
            {
                $aFinalSurveys[]=$aStatSurvey;
            }
        }
        $this->aRenderData['aSurveys']=$aFinalSurveys;
        $this->render('list_surveys');
    }

    private function onlyStatAccess()
    {
        if(Yii::app() instanceof CConsoleApplication)
        {
            return;
        }
        if(!Yii::app()->session['loginID'])
        {
            return;
        }
        //~ $oCriteria=new CdbCriteria();
        //~ $oCriteria->condition="t.sid=:sid and t.language=:language";
        $countPermission=Permission::model()->count(
            "uid=:uid AND permission NOT LIKE :permission AND entity='global' AND (create_p > 0 or read_p > 0 or update_p > 0 or delete_p > 0 or import_p > 0 or import_p > 0)",
            array(":uid"=>Yii::app()->session['loginID'],':permission' => "auth%")
        );
        return !(bool)$countPermission;
    }
    /**
     * rendering a file in plugin view
     */
    private function render($fileRender)
    {
        Yii::app()->bootstrap->init();
        Yii::setPathOfAlias('adminStats', dirname(__FILE__));
        $oEvent=$this->event;
        Yii::app()->controller->layout='bare'; // bare don't have any HTML
        $this->aRenderData['assetUrl']=$sAssetUrl=Yii::app()->assetManager->publish(dirname(__FILE__) . '/assets');
        //~ $this->aRenderData['chartjsUrl']=Yii::app()->assetManager->publish(dirname(__FILE__) . '/vendor/Chart.js');
        $this->aRenderData['jqplotUrl']=Yii::app()->assetManager->publish(dirname(__FILE__) . '/vendor/jquery.jqplot');
        $this->aRenderData['subview']="subviews.{$fileRender}";
        $this->aRenderData['surveyList']=$this->getSurveyList();
        $this->aRenderData['showSatisfaction']=count($this->get("questionNumeric","Survey",$this->iSurveyId,array()));
        $this->aRenderData['showAdminSurvey']=Permission::model()->hasSurveyPermission($this->iSurveyId,'surveysettings','update') && !$this->onlyStatAccess();
        $this->aRenderData['showAdmin']=!$this->onlyStatAccess();

        Yii::app()->controller->render("adminStats.views.layout",$this->aRenderData);
    }
    /**
     * Return the survey with allowed access
     */
    private function getSurveyList()
    {
        static $aStatSurveys;
        if(null!==$aStatSurveys)
            return $aStatSurveys;

        $oCriteria=new CdbCriteria();
        $oCriteria->condition='active=:active';
        $oCriteria->params[':active'] = "Y";
        if(!Permission::model()->hasGlobalPermission('surveys','read'))
        {
            $oCriteria->addCondition('sid IN (SELECT entity_id FROM {{permissions}} WHERE entity = :entity AND  uid = :uid AND permission = :permission AND read_p = 1)');
            $oCriteria->params[':entity'] = "survey";
            $oCriteria->params[':uid'] = Yii::app()->user->getId();
            $oCriteria->params[':permission'] = 'statistics';
            $oCriteria->compare('owner_id',Yii::app()->user->getId(),false,'OR');
        }
        $aSurveys = Survey::model()->with(array('languagesettings'=>array('condition'=>'surveyls_language=language')))->findAll($oCriteria);
        $aStatSurveys= array();
        if(!empty($aSurveys))
        {
            foreach($aSurveys as $oSurvey)
            {
                if(tableExists("{{survey_{$oSurvey->sid}}}"))
                {
                    $title=$this->get("alternateTitle","Survey",$oSurvey->sid,"");
                    if(empty($title)){
                        $title=$oSurvey->defaultlanguage->surveyls_title;
                    }
                    $aStatSurveys[]=array_merge($oSurvey->attributes, $oSurvey->defaultlanguage->attributes,array('title'=>$title));
                }
            }
        }
        return $aStatSurveys;
    }


    /**
     * Update plugin settings for the link
     */
    public function getPluginSettings($getValues=true)
    {
        $url=$this->api->createUrl('plugins/direct', array('plugin' => $this->getName(), 'function' => 'list'));
        $this->settings['docu']['content']= "<p class='alert alert-info'>The link to the statitsics is : <a href='{$url}'>{$url}</a></p>";
        return parent::getPluginSettings($getValues);
    }

    /**
     * Get the moyenne for a numeric question type
     * @param : $sColumn : column title
     * @return float|false
     */
    private function getAverage($sColumn,$aConditions=null)
    {
        //~ $aAverage=array(); // Go to cache ?
        //~ if(isset($aAverage[$sColumn]))
            //~ return $aAverage[$sColumn];
        $sQuotedColumn=Yii::app()->db->quoteColumnName($sColumn);
        $iTotal=$this->getCountNumeric($sColumn,$aConditions);
        if($iTotal <= 0)
        {
            $average=false;
            return $average;
        }
        $oCriteria = new CDbCriteria;
        $oCriteria->select="SUM({$sQuotedColumn}) as SUM";
        $oCriteria->condition="submitdate IS NOT NULL";
        $oCriteria->addCondition("concat('',{$sQuotedColumn} * 1) = {$sQuotedColumn}");
        if(empty($aConditions))
        {

            $iSum = (int) Yii::app()->db->getCommandBuilder()->createFindCommand(SurveyDynamic::model($this->iSurveyId)->getTableSchema(),$oCriteria)->queryScalar();
        }
        else
        {
            foreach($aConditions as $column=>$values)
            {
                if(is_array($values)){
                    $oCriteria->addInCondition($column,$values);
                }else{
                    $oCriteria->compare($column,$values);
                }
            }
            if(tableExists("{{tokens_{$this->iSurveyId}}}"))
            {
                /* Manually construct where command ... */
                $sSelect = "submitdate IS NOT NULL"
                         . " AND concat('',{$sQuotedColumn} * 1) = {$sQuotedColumn}";
                $params=array();
                $countParams=1;
                foreach($aConditions as $column=>$values)
                {
                    if(is_array($values))
                    {
                        $valParams=array();
                        foreach($values as $value)
                        {
                            $valParams[]=":p{$countParams}";
                            $params[":p{$countParams}"]=$value;
                            $countParams++;
                        }
                        $sSelect .= " AND ".Yii::app()->db->quoteColumnName($column)." IN (".implode(",",$valParams).")";
                    }else{
                        $params[":p{$countParams}"]=$values;
                        $sSelect .= " AND ".Yii::app()->db->quoteColumnName($column)."= :p{$countParams}";
                        $countParams++;
                    }
                }
                $iSum=(int) Yii::app()->db->createCommand()
                    ->select("SUM({$sQuotedColumn}) as SUM")
                    ->from("{{survey_{$this->iSurveyId}}} s")
                    ->join("{{tokens_{$this->iSurveyId}}} t", 's.token=t.token')
                    ->where($sSelect,$params)
                    ->queryScalar();

            }
            else
            {
                $iSum = (int) Yii::app()->db->getCommandBuilder()->createFindCommand(SurveyDynamic::model($this->iSurveyId)->getTableSchema(),$oCriteria)->queryScalar();
            }
        }
        if($iTotal > 0)
        {
            $average=$iSum/$iTotal;
        }
        else
        {
            $average=false;
        }
        return $average;
    }
    /**
     * Get the maximum numeric value question type
     * @todo fix it, control it
     * @param : $sColumn : column title
     * @return float|false
     */
    private function getMax($sColumn,$aCondition=null)
    {
        $aMax=array(); // Go to cache ?
        //~ if(isset($aMax[$sColumn]))
            //~ return $aMax[$sColumn];
        $sQuotedColumn=Yii::app()->db->quoteColumnName($sColumn);
        $oCriteria = new CDbCriteria;
        $oCriteria->select="MAX({$sQuotedColumn})";
        $oCriteria->condition="submitdate IS NOT NULL";
        $oCriteria->addCondition("concat('',{$sQuotedColumn} * 1) = {$sQuotedColumn}");

        $iMax = Yii::app()->db->getCommandBuilder()->createFindCommand(SurveyDynamic::model($this->iSurveyId)->getTableSchema(),$oCriteria)->queryScalar();

        if($iMax > 0)
        {
            $aMax[$sColumn]=$iMax;
        }
        else
        {
            $aMax[$sColumn]=false;
        }
        return $aMax[$sColumn];
    }
    /**
     * Get the count of answered for a numeric question type (only numeric answers)
     * @param string $sColumn : column title
     * @param array $aCondition
     * @return integer
     */
    private function getCountNumeric($sColumn,$aConditions=null)
    {
        //~ $aCountNumeric=array(); // Go to cache ?
        //~ if(isset($aCountNumeric[$sColumn]))
            //~ return $aCountNumeric[$sColumn];
        $sQuotedColumn=Yii::app()->db->quoteColumnName($sColumn);
        $oCriteria= new CDbCriteria;
        $oCriteria->condition="submitdate IS NOT NULL";
        $oCriteria->addCondition("concat('',{$sQuotedColumn} * 1) = {$sQuotedColumn}");
        if(empty($aConditions))
        {
            $iCountNumeric=(int)Response::model($this->iSurveyId)->count($oCriteria);
        }
        else
        {

            foreach($aConditions as $column=>$values)
            {
                if(is_array($values))
                {
                    $oCriteria->addInCondition($column,$values);
                }
                else
                {
                    $oCriteria->compare($column,$values);
                }
            }
            if(tableExists("{{tokens_{$this->iSurveyId}}}"))
            {
                $iCountNumeric=(int) Response::model($this->iSurveyId)->with('token')->count($oCriteria);
            }
            else
            {
                $iCountNumeric=(int) Response::model($this->iSurveyId)->count($oCriteria);
            }
        }
        return $iCountNumeric;
    }
    /**
     * get the array of token values
     * @param string attribute to take (column name)
     */
    private function getTokenValues($attribute)
    {
        $oTokenValues=Token::model($this->iSurveyId)->findAll(array(
            'select'=>$attribute,
            'condition'=>"$attribute !='' and $attribute is not null",
            'group'=>$attribute,
            'order'=>$attribute,
            'distinct'=>true,
        ));
        if($oTokenValues)
        {
            $aTokenValues=CHtml::listData($oTokenValues,$attribute,$attribute);
            foreach($this->aPushTokenValue as $sPushToken)
            {
                if(array_key_exists($sPushToken,$aTokenValues))
                {
                    unset($aTokenValues[$sPushToken]);
                    $aTokenValues[$sPushToken]=$sPushToken;
                }
            }
            return $aTokenValues;
        }
    }

}
