<?php
/**
 * Created by PhpStorm.
 * User: tuanla
 * Date: 2018-12-04
 * Time: 16:19
 */

namespace App\Jobs;

interface JobInterface
{
    public function getJobName();
    public function getJobDetails();
}
