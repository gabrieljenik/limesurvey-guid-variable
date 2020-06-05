<?php
/**
 * Add a GUID variable to expression manager to be used on question text, help and answers.
 * Expected to be used as prefiller for an equation question.
 */
class GuidVariable extends PluginBase {
    protected $storage = 'DbStorage';
    static protected $description = 'Adds a GUID variable to expression manager to be used on question text, help and answers.';
    static protected $name = 'GUID Variable';

    const GUID_LENGTH = 15;

    public function init()
    {
        $this->subscribe('beforeSurveySettings');
        $this->subscribe('newSurveySettings');

        $this->subscribe('beforeQuestionRender');
    }

    /**
     * Survey Settings
     */
    public function beforeSurveySettings()
    {
        $event = $this->event;
        $event->set("surveysettings.{$this->id}", array(
            'name' => get_class($this),
            'settings' => array(
                // Activate
                'active' => array(
                    'type' => 'boolean',
                    'default' => 1,
                    'label' => 'Activate plugin for this survey:',
                    'current' => $this->get('active', 'Survey', $event->get('survey'))
                ),

                // Variable Name
                'guid_var' => array(
                    'type' => 'string',
                    'label' => 'Name of the GUID variable:',
                    'help' => 'This will be the name of the GUID variable and will be used for replacing it with a GUID.',
                    'current' => $this->get('guid_var', 'Survey', $event->get('survey')),
                    'default' => '_guid',
                ),
            ),
        ));
    }
    public function newSurveySettings()
    {
        $event = $this->event;
        foreach ($event->get('settings') as $name => $value)
        {
            $this->set($name, $value, 'Survey', $event->get('survey'));
        }
    }

    /**
     * Checks a survey level setting
     */
    protected function checkSettingSurvey($setting, $surveyId = NULL, $surveyAtt = 'surveyId')
    {
        $event = $this->getEvent();
        if (empty($surveyId)) $surveyId = $event->get($surveyAtt);

        return $this->get($setting, 'Survey', $surveyId);
    }

    protected function checkSettingSurvey_Bool($setting, $surveyId = NULL, $surveyAtt = 'surveyId')
    {
        $val = $this->checkSettingSurvey($setting, $surveyId, $surveyAtt);
        return ($val == TRUE || $val > 0);
    }

    protected function IsPluginSurveyActive($surveyId = NULL, $surveyAtt = 'surveyId')
    {
        return $this->checkSettingSurvey_Bool('active', $surveyId, $surveyAtt);
    }

    private static $_guidvar = NULL;
    protected function GetGuidVar($surveyId = NULL)
    {
        if (!empty(self::$_guidvar)) return self::$_guidvar;

        self::$_guidvar = $this->checkSettingSurvey('guid_var', $surveyId);
        return self::$_guidvar;
    }
    /**
     * Do replacements at answer level, help text level and also question body
     */
    public function beforeQuestionRender()
    {
        // Return if not active
        if (!($this->IsPluginSurveyActive())) return;

        // Init
        $oEvent=$this->getEvent();
        $this->initGUID();

        // $sid = $oEvent->get("surveyId");
        // $guidvar = $this->GetGuidVar($sid);

        // Fetch Question
        $qid = $oEvent->get("qid");
        $oQuestion = $this->getQuestion($qid);
        if (empty($oQuestion)) return;

        // Fetch initial value
        $text = $oEvent->get('text');
        $help = $oEvent->get('help');
        $answers = $oEvent->get('answers');

        // Do replacements
        $oEvent->set('text',$this->doReplacement($text,$qid));
        $oEvent->set('questionhelp',$this->doReplacement($help));/* pre 3.0 version */
        $oEvent->set('help',$this->doReplacement($help));/* 3.0 and version */
        $oEvent->set('answers',$this->doReplacement($answers));/* in 3.X and up Expression manager already happen */
    }

    private static $_guid = NULL;
    protected function initGUID()
    {
        $event=$this->getEvent();
        $surveyId = $event->get("surveyId");
        $qid = $event->get("qid");
        $prefix = $_SESSION['session_hash'] . $surveyId . $qid . rand();

        self::$_guid = substr(hash('sha256',uniqid($prefix, true)), 0, self::GUID_LENGTH);

        return self::$_guid;
    }
    protected function getGUID()
    {
        if (empty(self::$_guid)) $this->initGUID();
        return self::$_guid;
    }

    protected function getQuestion($qid, $language = NULL)
    {
        // Init lang
        if(empty($lang)) $lang = App()->getLanguage();

        // Get Question
        if(intval(App()->getConfig('versionnumber')) < 4) {
            $oQuestion = Question::model()->find("qid = :qid and language = :language",array(":qid"=>$qid,":language" => $lang));
        } else {
            $oQuestion = QuestionL10n::model()->find("qid = :qid and language = :language",array(":qid"=>$qid,":language" => $lang));
        }

        return $oQuestion;
    }

    protected function doReplacement($text)
    {
        $guidvar = $this->GetGuidVar();
        $guid = $this->getGUID();

        $ori = $text;
        // echo "Replacing $guidvar by $guid on <pre>$text</pre>";
        $text = str_replace("[". $guidvar . "]", $guid, $text);

        return $text;
    }

}
