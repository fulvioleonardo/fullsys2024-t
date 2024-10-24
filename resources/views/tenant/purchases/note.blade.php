@extends('tenant.layouts.app')

@section('content')

    <tenant-purchases-note-form :resource-id="{{json_encode($resourceId)}}"></tenant-purchases-note-form>

@endsection