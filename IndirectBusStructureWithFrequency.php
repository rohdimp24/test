<?php
   class IndirectBusStructureWithFrequency
   {
	   private $firstBus;
	   private $secondBus;
	   //private $distance;
       private $junction1;
       private $junction2;
       private $firstBusFrequency;
       private $secondBusFrequency;
       private $junctionFrequency;
       private $distBetweenJunction;
       private $collectiveFrequency;
       private $totalRouteDistance;

	   public function __construct($firstBus,$firstBusFrequency,$secondBus,$secondBusFrequency,
                                   $junction1,$junction2,$junctionFrequency,$distBetweenJunction,
                                   $collectiveFrequency,$totalRouteDistance)
	   {
			$this->firstBus=$firstBus;
			$this->secondBus=$secondBus;
			$this->distBetweenJunction=$distBetweenJunction;
            $this->junction1=$junction1;
            $this->junction2=$junction2;
            $this->collectiveFrequency=$collectiveFrequency;
            $this->junctionFrequency=$junctionFrequency;
            $this->firstBusFrequency=$firstBusFrequency;
           $this->secondBusFrequency=$secondBusFrequency;
           $this->totalRouteDistance=$totalRouteDistance;
	   }
	   
	   	
	   public function getFirstBus()
	   {
		   return $this->firstBus;		   
	   }
		
		public function getSecondBus()
	   {
		   return $this->secondBus;		   
	   }

		public function getDistanceBetweenJunctions()
	   {
			return $this->distBetweenJunction;
	   }

       public function getJunction1()
       {
           return $this->junction1;
       }

       public function getJunction2()
       {
           return $this->junction2;
       }

       public function getJunctionFrequency()
       {
           return $this->junctionFrequency;
       }

       public function getCollectiveFrequency()
       {
           return $this->collectiveFrequency;
       }

       public function getFirstBusFrequency()
       {
           return $this->firstBusFrequency;
       }

       public function getSecondBusFrequency()
       {
           return $this->secondBusFrequency;
       }

       public function getTotalRouteDistance()
       {
           return $this->totalRouteDistance;
       }

       public function setCollectiveFrequency($frequency)
       {
           $this->collectiveFrequency=$frequency;
       }



   }

?>