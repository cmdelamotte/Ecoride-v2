<?php

namespace App\Controllers;

use App\Core\Controller;

class HomeController extends Controller
{
    public function index()
    {
        $this->render('home');
    }

    public function legalMentions()
    {
        $this->render('legal_mentions');
    }
}