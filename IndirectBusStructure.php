<?php
   class IndirectBusStructure
   {
	   private $firstBus;
	   private $secondBus;
	   private $distance;
	  
	   
	   public function __construct($firstBus,$secondBus,$distance)
	   {
			$this->firstBus=$firstBus;
			$this->secondBus=$secondBus;
			$this->distance=$distance;			
	   }
	   
	   	
	   public function getFirstBus()
	   {
		   return $this->firstBus;		   
	   }
		
		public function getSecondBus()
	   {
		   return $this->secondBus;		   
	   }

		public function getDistance()
	   {
			return $this->distance;
	   }
   }

?>