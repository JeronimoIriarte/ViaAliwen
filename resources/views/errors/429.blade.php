@extends('errors.plantilla')

@section('titulo', 'Demasiados intentos')
@section('icono', 'bi-stopwatch')
@section('codigo', '429')
@section('titulo_error', 'Demasiados intentos')
@section('mensaje', 'Hiciste muchos intentos seguidos. Esperá un minuto y volvé a probar.')
