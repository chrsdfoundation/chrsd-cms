{{-- Minimal child certificate view. Every field is a view variable passed
     from the controller — `certificates.layouts.premium` reads them directly.
     Child views only need @extends if they want to override HTML slots
     (`@section('brand') … @endsection`, `@section('seal') … @endsection`,
     `@section('content-extra') … @endsection`); otherwise plain view() with
     data on either this file or the layout itself is enough. --}}
@extends('certificates.layouts.premium')
