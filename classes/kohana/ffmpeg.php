<?php class ffmpeg{
	
	public $config;
	
	public $file_name;
	
	private $file;
	
	public $id;
	
	public $time;
	
	public $offset;
	
	public function __construct( $group = 'default' ){
	
		// load in config
		$this->config = (object) Kohana::config('ffmpeg')->get($group);
		
	}
	
	public function read_offset( $file = false ) {
		if( !empty($file) )
			$this->file_name = $file;
			
		if( empty($this->file_name) ){
			// make sure to set a file to work on
			Kohana::$log->add( Kohana::ERROR, 'Need to set an file in ffmpeg: ' );
			return false;
		}
		
		$this->file = $this->find_media();
		$sample_path = $this->image_path('sample');
		$res = $this->create($sample_path);
		if ( !$res['success'] ) {
			throw new Kohana_Exception('Failed to grab sample frame to file :var', array(':var' => $sample_path));
		} else {
			$pat = '/Duration:\s.*, start: (\d+)\.(-?)(\d+), /';
			$m = array();
			if (preg_match($pat, $res['log'], $m)) {
				if ($m[2]=='-') {
				  $n = '-'.$m[1].'.'.$m[3];
				} else {
				  $n = $m[1].'.'.$m[3];
				}
				return (float) $n;
			} else {
				throw new Kohana_Exception("Couldn't find start in log:\n:log", array(':log' => $res['log']));
			}
		}
	}
	
	public function grab_frame( $file = false ){
		
		if( !empty($file) )
			$this->file_name = $file;
		
		if( empty($this->file_name) ){
			// make sure to set a file to work on
			Kohana::$log->add( Kohana::ERROR, 'Need to set an file in ffmpeg: ' );
			return false;
		}
		
		if ( empty($this->offset)) {
			// Initialize to 0, then read real value
			$this->offset = 0.0;
			$this->offset = $this->read_offset($file);
		}
		if ( empty($this->time)) $this->time = 0.0;
		
		$this->file = $this->find_media();
		$thumb_name = $this->image_filename('thumb');
		$thumb_path = $this->image_path('thumb');
		$full_name = $this->image_filename('full');
		$full_path = $this->image_path('full');
		$print_name = $this->image_filename('print');
		$print_path = $this->image_path('print');
		
		// Max-size print image
		$res = $this->create($print_path);
		if (! $res['success'] ) {
			throw new Kohana_Exception("Failed to grab print frame to file :var\n:log", array(':var' => $print_path, ':log' => $res['log']));
		}

		// full thumbnail
		$image = Image::factory( $print_path );
		$image->resize( $this->config->full_width, $this->config->full_width+100, Image::AUTO )
		->crop( $this->config->full_width, $this->config->full_height )
		->save( $full_path );

		// cropped thumbnail
		$image = Image::factory( $print_path );
		$image->resize( $this->config->thumb_width, $this->config->thumb_width+100, Image::AUTO )
		->crop( $this->config->thumb_width, $this->config->thumb_height )
		->save( $thumb_path );
			
		return array(
			'thumb' => $thumb_name,
			'full' => $full_name,
			'print' => $print_name,
		);
	}
	
	public function create($dest_file){
		$time = (float)($this->time - $this->offset - (1.0/30.0));
		$sec = (int) floor((float)($time));
		$rsec = $sec;
		$ftime = ((float)$time) - $sec;
		$hour = ($sec - ($sec % 3600)) / 3600;
		$sec -= ($hour * 3600);
		$min = ($sec - ($sec % 60)) / 60;
		$sec -= ($min * 60);
		$fsec = (float)$sec + $ftime;
		$fmt = "$hour:$min:$fsec";
		
		Kohana::$log->add( Kohana::DEBUG, "ffmpeg frame grab: in time :it, offset :o, final time :ft, ffmpeg format :fmt",
			array(
				':it' =>$this->time,
				':o'  =>$this->offset,
				':ft' =>$time,
				':fmt'=>$fmt));
				
		$dest_dir = dirname($dest_file);
		if (!file_exists($dest_dir)) mkdir($dest_dir, 0770, true);
		$cmd = $this->config->ffmpeg_loc." -i '{$this->file}' -ss $fmt -vframes 1 -f image2 '$dest_file'";
		$out = shell_exec("$cmd 2>&1");
		$success = TRUE;
		if (!file_exists($dest_file)) {
			$success = FALSE;
			Kohana::$log->add( Kohana::ERROR, "ffmpeg frame grab failed.  Output:\n".$out);
		}
		return array( 'success'=>$success, 'log'=> $out );
	}
	
	public function file_path(){
		return DOCROOT.$this->config->upload_dir.DIRECTORY_SEPARATOR.$this->file_name;
	}
	
	public function file_directory(){
	//	$this->file_name
		return DOCROOT.$this->config->upload_dir.DIRECTORY_SEPARATOR.$this->file_name;
	}
	
	public function image_directory(){
		return DOCROOT.$this->config->upload_dir;
	}
	
	public function image_filename($type) {
		$filename_format = "video-%d.%s.%0.3f.jpg";
		return sprintf($filename_format, $this->id, $type, $this->time);
	}
	
	public function image_path($type) {
		return $this->image_directory().DIRECTORY_SEPARATOR.$this->image_filename($type);
	}
	
	public function find_media( $file_name = false, $root = false ){
		
		$path = $this->file_path();
		return ( is_file($path) ) ? $path : false;

	}
}