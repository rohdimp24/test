<?php
   class BusStopsDataStructure
   {
	   private $StopName;
	   private $Latitude;
	   private $Longitude;
	  
	   
	   public function __construct($StopName,$Latitude,$Longitude)
	   {
			$this->StopName=$StopName;
			$this->Latitude=$Latitude;
			$this->Longitude=$Longitude;			
	   }
	   
	   	
	   public function getLatitude()
	   {
		   return $this->Latitude;		   
	   }
		
		public function getLongitude()
	   {
		   return $this->Longitude;		   
	   }

		public function getStopName()
	   {
			return $this->StopName;
	   }
   }

?>