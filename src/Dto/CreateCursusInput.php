<?php

namespace App\Dto;

class CreateCursusInput
{
  public string $name;
  public string $price;
  /** @var int[] */
    public array $lessons = [];
  }