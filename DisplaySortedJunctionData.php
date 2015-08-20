<?php
/**
 * Created by JetBrains PhpStorm.
 * User: fz015992
 * Date: 9/23/13
 * Time: 10:25 PM
 * To change this template use File | Settings | File Templates.
 */

class DisplaySortedJunctionsData
{
    private $junctionString;
    private $startBuses;
    private $endBuses;
    private $junction1Frequency;
    private $junction2Frequency;
    private $junction1;
    private $junction2;
    private $junctionDistance;
    private $startStop;
    private $endStop;
    private $totalDistance;

    public function __construct($junctionString,$totalDistance,$startStop,$endStop,$junction1,$junction2,$junctionDistance)
    {
        $this->junctionString=$junctionString;
        $this->totalDistance=$totalDistance;
        $this->startStop=$startStop;
        $this->endStop=$endStop;
        $this->junctionDistance=$junctionDistance;
        $this->junction1=$junction1;
        $this->junction2=$junction2;
    }


    public function getJunctionString()
    {
        return $this->junctionString;
    }

    public function getStartStop()
    {
        return $this->startStop;
    }

    public function getEndStop()
    {
        return $this->endStop;
    }


    public function getJunctionDistance()
    {
        return $this->junctionDistance;
    }


    public function getFirstJunction()
    {
        return $this->junction1;
    }


    public function getSecondJunction()
    {
        return $this->junction2;
    }


    public function getFirstJunctionFrequency()
    {
        return $this->junction1Frequency;
    }

    public function getSecondJunctionFrequency()
    {
        return $this->junction2Frequency;
    }


    public function setFirstJunctionFrequency($freq)
    {
        $this->junction1Frequency=$freq;
    }

    public function setSecondJunctionFrequency($freq)
    {
        $this->junction2Frequency=$freq;
    }


    public function setStartBusString($startBuses)
    {
        $this->startBuses=$startBuses;
    }

    public function getStartBusString()
    {
        return $this->startBuses;
    }

    public function setEndBusString($endBuses)
    {
        $this->endBuses=$endBuses;
    }

    public function getEndBusString()
    {
        return $this->endBuses;
    }

    public function getCollectiveFrequency()
    {
        return $this->junction1Frequency+$this->junction2Frequency;
    }

    public function getTotalRouteDistance()
    {
        return $this->totalDistance;
    }


}