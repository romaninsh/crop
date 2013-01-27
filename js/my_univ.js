$.each({

  cropSelectSetTarget: function(field){
  	this.cropSelectTarget=field;
  },
  cropSelectTarget: null,
  cropSelect: function(a){
    $(this.cropSelectTarget).val(this.toJSON(a));
  },
  uploadHandler: function(crop_url,reload_url){
  	var uploader=this.jquery;
  	$('.spinner').hide();

  	reload_url = $.atk4.addArgument(reload_url);
    crop_url = $.atk4.addArgument(crop_url);

  	this.dialogURL('Crop and Save',crop_url,{close: function(){
  		document.location=reload_url;
  	}});

  },
  ratingDialogURL: function(title,url,options,callback){
    options=$.extend({
      resizable: false,
      dialogClass: 'photo-viewer photo-rating',
      closeOnEscape: false,
      open: function(event, ui) { 
        $(".ui-dialog-titlebar").remove(); 
        $(".ui-dialog-buttonpane").find('button').button('disable');
      },
      buttons: {
          /*
        'Ok': function(){
          var f=$(this).find('form');
          if(!f.hasClass('form_changed')){
            alert('rate first!');
            return;
          }
          if(f.length)f.eq(0).submit(); else $(this).dialog('close');
        }
       */
      }
    },options);
    return this.dialogURL(title,url,options,callback);
  },
  /* Will allow cycling through images when you click on next / prev buttons */
  imagePrevNext: function(){
      var $self=this.jquery;
      $self.find('.picture:first').show();

      var cl=function(dir){
          // find current image
          var p=$self.find('.picture:visible');
          var n;
          if(dir==1){
              n=p.next();
              if(!n.length)n=$self.find('.picture:first');
          }else{
              n=p.prev();
              if(!n.length)n=$self.find('.picture:last');
          }
          p.fadeOut('fast',function(){n.fadeIn('fast');});
      }
  },
  charLimit: function(num){
      this.jquery.keyup(function () {
      var $this      = $(this),
          charLength = $this.val().length,
          charLimit  = num;
  // Displays count
//      $this.next("ins").html(charLength + " of " + charLimit + " characters used");
          $this.next("ins").find('span').text(charLimit-charLength);
  // Alert when max is reached
      if ($this.val().length > charLimit) {
          $this.next("ins").find('span').text(charLimit-charLength);
          //           $this.next("ins").html("<strong>You may only have up to " + charLimit + " characters.</strong>");

           $this.val($this.val().substr(0,num));
      }
    });
      this.jquery.keyup();
  }


},$.univ._import);
