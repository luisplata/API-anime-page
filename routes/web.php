<?php

use Illuminate\Support\Facades\Route;

Route::redirect('/', env("url_front"), 301);
