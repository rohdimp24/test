<?php
   class IntersectionBusStructure
   {
	   private $firstBus;
	   private $firstBusStop;
	   private $secondBus;
	   private $secondBusStop;
	  
	  
	   
	   public function __construct($firstBus,$firstBusStop,$secondBus,$secondBusStop)
	   {
			$this->firstBus=$firstBus;
			$this->firstBusStop=$firstBusStop;
			$this->secondBus=$secondBus;
			$this->secondBusStop=$secondBusStop;
			//$this->distance=$distance;			
	   }
	   
	   	
	   public function getFirstBus()
	   {
		   return $this->firstBus;		   
	   }
		
	   public function getFirstBusStop()
	   {
		   return $this->firstBusStop;		   
	   }

		public function getSecondBus()
	   {
		   return $this->secondBus;		   
	   }

		public function getSecondBusStop()
	   {
		   return $this->secondBusStop;	   
	   }
		
   }

?>