<?php
/**
 * MediaElement
 *
 * This ext allow you to add HTML5 audio and video player using mediaElement JS library to your Yii project.
 *
 * @version 1.0
 * @author Shiv Charan Panjeta <shiv@toxsl.com> <shivcharan.panjeta@outlook.com>
 */
/**
 *
 * Usage:
 *
 * $this->widget ( 'ext.mediaElement.MediaElementPortlet',
 * array (
 * 'url' => 'http://www.toxsl.com/test/bunny.mp4',
 * //'model' => $model,
 * //'attribute' => 'url'
 * //'mimeType' => 'audio/mp3',
 * // see code for details about sources implementation
 * //'useMultipleSources' => true,
 * //'sources' => array(),
 *
 * ));
 */

Yii::import('zii.widgets.CPortlet');

class MediaElementPortlet extends CPortlet
{
    public $attribute = null;
    public $model = null;
    public $url = null;
    public $mimeType = null;
    public $mediaType = 'audio';
    public $autoplay = true;
    public $htmlOptions = array();
    public $disableRightClick = false;

    /**
     * Whether we have multiple media options to choose from for various browsers
     * @var bool
     */
    public $useMultipleSources = false;

    /**
     * Allows optional provision of multiple source videos to be chosen between intelligently by MediaElement / browsers
     * @var array
     * array(
     *      'type' => 'video/mp4|video/webm|video/ogg',
     *      'src' => '/video/file.mp4',
     * )
     */
    public $sources = array();

    /**
     * Allows specifying additional information for our <object> tag flash fallback
     * @var array
     * array(
     *      'htmlOptions' => array(), // items to be added to our object tag directly (overrides main settings)
     *      'contentTags' => array(
     *          array('tag' => 'param', 'htmlOptions' => array()),
     *      ),
     *      // above overrides our defaults
     * )
     */
    public $objectTag = array();

    public $scriptUrl = null;
    public $scriptFile = array('mediaelement-and-player.js');
    public $cssFile = array('mediaelementplayer.css', 'mejs-skins.css');

    protected function registerScriptFile($fileName, $position = CClientScript::POS_HEAD)
    {
        Yii::app()->clientScript->registerScriptFile($this->scriptUrl . '/' . $fileName, $position);
    }

    protected function registerCssFile($fileName)
    {
        Yii::app()->clientScript->registerCssFile($this->scriptUrl . '/' . $fileName);
    }

    protected function resolvePackagePath()
    {
        if ($this->scriptUrl === null) {
            $basePath = __DIR__ . '/assets';
            $baseUrl = Yii::app()->getAssetManager()->publish($basePath);
            if ($this->scriptUrl === null)
                $this->scriptUrl = $baseUrl . '';
        }
    }

    protected function registerCoreScripts()
    {
        $cs = Yii::app()->getClientScript();
        if (is_string($this->cssFile))
            $this->registerCssFile($this->cssFile);
        else if (is_array($this->cssFile)) {
            foreach ($this->cssFile as $cssFile)
                $this->registerCssFile($cssFile);
        }

        $cs->registerCoreScript('jquery');

        if (is_string($this->scriptFile))
            $this->registerScriptFile($this->scriptFile);
        else if (is_array($this->scriptFile)) {
            foreach ($this->scriptFile as $scriptFile)
                $this->registerScriptFile($scriptFile);
        }
    }

    public function init()
    {
        parent::init();

        $model = $this->model;
        $att = $this->attribute;
        if ($this->url == null) $this->url = $model->$att;
        if ($this->mimeType == null) $this->mimeType = CFileHelper::getMimeType($this->url);
        if ($this->mimeType == null) $this->mimeType = "audio/mp3";
        list ($type, $codec) = explode('/', $this->mimeType);

        if ($type != null) {
            if ($type == 'audio' || $type == 'video') $this->mediaType = $type;
        }
        if (!isset($this->htmlOptions['id']))
            $this->htmlOptions['id'] = $this->getId();

        // adjust our html options if needed
        if (!isset($this->htmlOptions['type'])) {
            $this->htmlOptions['type'] = $this->mimeType;
        }

        // add in our other custom options
        $this->htmlOptions['controls'] = "controls";
        $this->htmlOptions['src'] = $this->url;
        $this->htmlOptions['autoplay'] = $this->autoplay;

        $this->resolvePackagePath();
        $this->registerCoreScripts();

    }

    public function run()
    {
        parent::run();

        $tagContent = $this->tagContentGenerator();

        echo CHtml::tag(
            $this->mediaType,
            $this->htmlOptions,
            $tagContent
        );
        ?>

        <script>
            var player = new MediaElementPlayer('audio,video');
            <?php
            if($this->disableRightClick){
                echo 'var disableRightClick = true';
            }
            ?>
        </script>

        <?php

    }

    /**
     * Generates the appropriate HTML to be rendered inside of our tag, if any
     * @return boolean|string
     */
    protected function tagContentGenerator()
    {
        if (!$this->useMultipleSources)
            return false;

        $content = false;

        // handle adding our sources, if any
        if (isset($this->sources)) {
            foreach ($this->sources as $item) {
                $content .= CHtml::tag('source', $item);
            }

            // now, clear our main tag's src option, it's no longer useful, as we've added in source fallbacks instead
            // we'll let MediaElement handle that appropriately for us instead
            unset($this->htmlOptions['src']);
        }

        // now handle adding in a flash fallback as well
        $options = (isset($this->objectTag['htmlOptions']))
            ? $this->objectTag['htmlOptions']
            : array(
                'type' => 'application/x-shockwave-flash',
                'data' => $this->scriptUrl . '/flashmediaelement.swf',
            );
        $contentTags = (isset($this->objectTag['contentTags']))
            ? $this->objectTag['contentTags']
            : array(
                array(
                    'tag' => 'param',
                    'options' => array(
                        'name' => 'movie',
                        'value' => $this->scriptUrl . '/flashmediaelement.swf',
                    ),
                ),
                array(
                    'tag' => 'param',
                    'options' => array(
                        'name' => 'flashvars',
                        'value' => 'controls=true&file=' . $this->url,
                    ),
                ),
// @TODO: add img fallback implementation, love to have feedback/pull back here
//                array(
//                    'tag' => 'img',
//                    'options' => array(
//                        'src' => 'fallback.jpg'
//                    )
//                ),
            );

        $optionsContent = '';
        foreach ($contentTags as $tag) {
            $optionsContent .= CHtml::tag($tag['tag'], $tag['options']);
        }

        $content .= CHtml::tag('object', $options, $optionsContent);

        return $content;
    }
}