@extends('errors.plantilla')

@section('titulo', 'Acceso denegado')
@section('icono', 'bi-shield-lock')
@section('codigo', '403')
@section('titulo_error', 'No tenés permiso para entrar acá')
@section('mensaje', $exception->getMessage() ?: 'Esta sección está reservada para otro tipo de usuario.')
