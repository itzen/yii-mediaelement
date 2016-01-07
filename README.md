# yii-mediaelement
================
This ext allow you to add HTML5 audio and video player using mediaElement JS library to you Yii project.

Its using code from http://mediaelementjs.com/ (version 2.19.0).

# Requirements

Yii 1.1 or above

# Usage

Just use the widget anywhere in your view code.

```
$this->widget('ext.mediaElement.MediaElementPortlet',
    array( 
        'url' => 'http://download.blender.org/peach/bigbuckbunny_movies/BigBuckBunny_320x180.mp4',
        // or you can set the model and attributes
        //'model' => $model,
        //'attribute' => 'url'
        // its required and so you have to set correctly
        'mimeType' =>'video/mp4',
    )
);
```
