<?php
   class BusDataStructure
   {
	   private $StopName;
	   private $Latitude;
	   private $Longitude;
	   private $Distance;
	   
	   public function __construct($StopName,$Latitude,$Longitude,$Distance)
	   {
			$this->StopName=$StopName;
			$this->Latitude=$Latitude;
			$this->Longitude=$Longitude;			
			$this->Distance=$Distance;
			
	   }
	   
	   
		
	   public function getDistance()
	   {
		   return $this->Distance;		   
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