<?php
class page_user_pictures_crop extends Page {

	public $width=PIC_WIDTH;
	public $height=PIC_HEIGHT;

	function init(){
		parent::init();

		$this->api->stickyGET('id');
		$this->api->stickyGET('type');

		// Still need to check if picture was already in this spot before!

		// This is original image
		$i=$this->add('Model_File')->loadData($_GET['id']);
		$pic=$i->getPath();

		list($w, $h) = getimagesize($pic);

		if( $w < PIC_WIDTH || $h < PIC_HEIGHT ){										// pic not >= minimum size?
			$i->delete();												// then delete pic from database and - through hook - file system
			$this->add('View_Error')->set('Image is too small');		// Show error message
			throw $this->exception(null,'StopInit');					// and don't do anything else
		}

		$aspect = $w/$this->width;

		$f=$this->add('Form');
		$f->addField('line','description');
		$f->addField('hidden','coords');

		$this->api->jui->addStaticStylesheet('jcrop/css/jquery.Jcrop','.css','js');
		$this->js(true)->univ()->cropSelectSetTarget($f->getElement('coords'));
		$this->js(true)->_load('jcrop/js/jquery.Jcrop')->find('img.preview')->Jcrop(array(
			'onSelect'=> $this->js(null,'$.univ().cropSelect'),
			'aspectRatio'=>$this->width/$this->height,
			'setSelect'=>array(0,0, $this->height/$aspect, $this->width/$aspect),
			'minSize'=>array($this->height/$aspect, $this->width/$aspect),
			'allowSelect'=>false
			));

		$m=$this->add('Model_Picture')
			->addCondition('user_id',$this->api->auth->model->id)
			->addCondition('type',$_GET['type']);

		if($f->isSubmitted()){
			//$m->deleteAll();
			foreach($m as $junk){$m->delete();};

			$m->buildThumbnail($i, json_decode($f->get('coords')));
			$m->set('description',$f->get('description'));

			$m->update();
			$this->js()->univ()->location($this->api->url('..',array('id'=>false,'type'=>false)))->execute();
		}

		//$i=$m->ref('filestore_file_id');
		$this->template->set('src',$this->api->pm->base_path.$i->getPath());
		$this->template->set('width',$this->width);

	}
	function defaultTemplate(){
		return array('page/picturecrop');
	}
}