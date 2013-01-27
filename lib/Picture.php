<?php
class Model_Picture extends Model_Table {
	public $table="picture";

	public $thumb_width		= THUMB_WIDTH;
	public $thumb_height	= THUMB_HEIGHT;

	public $pic_width		= PIC_WIDTH;
	public $pic_height		= PIC_HEIGHT;
	

	function init(){
		parent::init();

		$this->hasOne('User')->defaultValue($this->api->auth->model->id);
		$this->addField('description');
		$this->addField('is_approved')->type('boolean');
		$this->addField('flagged')->type('boolean');

		$this->hasOne('Image','filestore_file_id','url');
		$this->addField('type')->enum(array('pr','fb','p1','p2','p3'));

		$this->addHook('beforeDelete',$this);

        $this->addExpression('thumb_url')->set(function($m,$q){
            $mm=$m->add('Model_File');
            $mm->join('filestore_image.thumb_file_id')
                ->addField('original_file_id');
            $mm->addCondition('original_file_id',$m->getElement('filestore_file_id'));
            return $mm->fieldQuery('url');
        });
		
	}
	function getURL(){
        return $this['filestore_file'];
	}
	function getThumbURL(){
        return $this['thumb_url'];
	}

	function buildThumbnail($big_picture, $crop_coordinates){

		// create new image
		$i=$this->ref('filestore_file_id','create');	// creates new entry and inserts ID into $this

		$src=$big_picture->getPath();
		$dst=$i->getPath();

		list($width, $height, $type) = getimagesize($src);

		ini_set("memory_limit","80M");
		$a=array(null,'gif','jpeg','png');
		$type=@$a[$type];																				// WHAT???
		$i['filestore_type_id']=$big_picture['filestore_type_id'];

		$fx="imagecreatefrom".$type;
		$myImage = $fx($src);

		// figure out aspect ratio
		$aspect = $width/$this->pic_width;	

		$x=$crop_coordinates->x*$aspect;
		$y=$crop_coordinates->y*$aspect;

		$w=$crop_coordinates->w*$aspect;
		$h=$crop_coordinates->h*$aspect;

		$crop=imagecreatetruecolor($this->pic_width,$this->pic_height);									// create empty thumbnail
		imagecopyresampled($crop,$myImage,0,0,$x,$y,$this->pic_width,$this->pic_height,$w,$h);			// resize original and copy into thumbnail
		imagejpeg($crop, $dst);
		imagedestroy($crop);

		$t=$i->ref('thumb_file_id','create');	// creates new entry and inserts ID into $this

		$thumb=imagecreatetruecolor($this->thumb_width,$this->thumb_height);							// create new empty pic
		imagecopyresampled($thumb,$myImage,0,0,$x,$y,$this->thumb_width,$this->thumb_height,$w,$h);		// resize original and copy into pic
		imagejpeg($thumb, $t->getPath());
		imagedestroy($thumb);

		imagedestroy($myImage);

		$big_picture->delete();
	}
	
	function beforeDelete(){
		$this->ref('filestore_file_id')->tryDelete();
	}

	/* Creates a sophisticated query for getting image paths */
	function getPathExpression($field='thumb_url',$is_thumb=true){
		// We will be building sub-query for the picture
		$p=$this->newInstance();

		// Picture needs to be joined with filestore
		$pic=$p
           ->join('filestore_file','filestore_file_id')
           ;

        // If we need thumbnail, that's few more joins
        if($is_thumb){
        	$pic=$pic
	        	->join('filestore_image.original_file_id')
	        	->join('filestore_file','thumb_file_id')
	        	;
        }

        // Finally we need volume
        $v=$pic->join('filestore_volume');

        // Construct the field
        $p->addExpression($field,function($m,$s)use($v,$p,$pic){
            return $s->expr(
                'COALESCE(
                        concat("'.$p->api->pm->base_path.'",'.
                            $v->fieldExpr('dirname').
                            ',"/",'.
                            $pic->fieldExpr('filename').
                        ')
                , "'.$p->api->locateURL('template','images/portrait.jpg').'") ');
        });
        return $p;
	}
}
