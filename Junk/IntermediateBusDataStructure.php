<?php
   class IntermediateBusDataStructure
   {
	   private $StartBusStopName;
	   private $EndBusStopName;
	   private $StartBusStopLatitude;
	   private $StartBusStopLongitude;
	   private $EndBusStopLatitude;
	   private $EndBusStopLongitude;
	   
	   private $Distance;
	   
	   public function __construct($StartBusStopName,$EndBusStopName,$StartBusStopLatitude,$StartBusStopLongitude,$EndBusStopLatitude,$EndBusStopLongitude,$Distance)
	   {
			$this->StartBusStopName=$StartBusStopName;
			$this->EndBusStopName=$EndBusStopName;
			$this->StartBusStopLatitude=$StartBusStopLatitude;
			$this->StartBusStopLongitude=$StartBusStopLongitude;
			$this->EndBusStopLatitude=$EndBusStopLatitude;
			$this->EndBusStopLongitude=$EndBusStopLongitude;
			$this->Distance=$Distance;
			
	   }
	   
	   
		
	   	
		public function getStartBusStopName()
	   {
			return $this->StartBusStopName;
	   }

	   public function getStartBusStopLatitude()
	   {
		   return $this->StartBusStopLatitude;		   
	   }
		
		public function getStartBusStopLongitude()
	   {
		   return$this->StartBusStopLongitude;	   
	   }

		
	   public function getEndBusStopName()
	   {
			return $this->EndBusStopName;
	   }

	     public function getEndBusStopLatitude()
	   {
		   return $this->EndBusStopLatitude;		   
	   }
		
		public function getEndBusStopLongitude()
	   {
		   return $this->EndBusStopLongitude;		   
	   }

		
	   public function getDistance()
	   {
		   return $this->Distance;		   
	   }
		
   }

?>