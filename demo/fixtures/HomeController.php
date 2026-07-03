<?php

namespace Demo\Fixtures;

/**
 * A sample annotated class the scanner will discover.
 */
class HomeController
{
    #[Route('GET', '/')]
    public function index()
    {
    }

    #[Route('GET', '/users/{id}')]
    public function show()
    {
    }

    #[Route('POST', '/users')]
    public function create()
    {
    }
}
