##Database Fields
<pre>
    create table users (
    id char(36) not null,
    file varchar(255) default null,
    dirname varchar(255) default null,
    basename varchar(255) default null,
    checksum varchar(255) default null,
    created datetime default null,
    modified datetime default null,
    primary key(id)
    );
</pre>

##Installation
1. Clone into /path/to/app/Plugin 
``git clone https://aeolu@github.com/aeolu/CakePHP-Media-Plugin.git Media``
2. Add as a submodule
``git submodule add https://aeolu@github.com/aeolu/CakePHP-Media-Plugin.git Plugin/Media``
3. Load the plugin.
<pre>
    ``//Within your bootstrap.php``
    ``CakePlugin::load('Media');``
    ``require APP . 'Plugin' . DS . 'Media' . DS . 'Config' . DS . 'core.php';``
</pre>
4. Initialize Media files.
``cake Media.Media init``
<pre>
---------------------------------------------------------------
Media Shell
---------------------------------------------------------------
Do you want to create missing media directories now?  
[n] > y``
/app/webroot/media/                               [OK  ]
/app/webroot/media/static/                        [OK  ]
/app/webroot/media/static/aud                     [OK  ]
/app/webroot/media/static/doc                     [OK  ]
/app/webroot/media/static/gen                     [OK  ]
/app/webroot/media/static/img                     [OK  ]
/app/webroot/media/static/vid                     [OK  ]
/app/webroot/media/transfer/                      [OK  ]
/app/webroot/media/transfer/aud                   [OK  ]
/app/webroot/media/transfer/doc                   [OK  ]
/app/webroot/media/transfer/gen                   [OK  ]
/app/webroot/media/transfer/img                   [OK  ]
/app/webroot/media/transfer/vid                   [OK  ]
/app/webroot/media/filter/                        [OK  ]

Your transfer directory is missing a htaccess file to block requests.
Do you want to create it now?  
[n] > n
</pre>

5. Set the permissions for the Media folder:
<pre>chmod 777 -R webroot/media/{transfer,filter}</pre>

##Upload and View Image
1. Set the model as media transfer user
<pre>
``<?php``
``class User extends AppModel {``
``	var $name = 'User';``
``        var $actsAs = array('Media.Transfer', 'Media.Coupler', 'Media.Meta');``
``?>``
</pre>
2. Set the form:
<pre>
``echo $this->Form->create('User', array('type'=>'file'));``
``echo $this->Form->input('file', array('type'=>'file'));``
``echo $this->Form->input('dirname', array('type'=>'hidden'));``
``echo $this->Form->input('basename', array('type'=>'hidden'));``
``echo $this->Form->input('checksum', array('type'=>'hidden'));``
``echo $this->Form->end(__('Submit'));``
</pre>
3. To view image:
<pre>
``<?php echo $this->Html->image( '../media/transfer/img/' . h($employee['User']['basename']), array('alt' => __('Profile Picture'), 'border' => '0')); ?>``
</pre>
4. ENJOY!