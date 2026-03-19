@extends('layouts.simple')

@section('content')
    <div class="container very-small mt-xl">
        <div class="card content-wrap auto-height">
            <h1 class="list-heading">{{ trans('auth.oidc_email_prompt_title') }}</h1>

            <p class="text-muted small">{{ trans('auth.oidc_email_prompt_desc') }}</p>

            <form action="{{ url('/oidc/email') }}" method="POST" class="stretch-inputs">
                {!! csrf_field() !!}

                <div class="form-group">
                    <label for="email">{{ trans('auth.email') }}</label>
                    @include('form.text', ['name' => 'email'])
                </div>

                <div class="form-group text-right mt-m">
                    <button class="button">{{ trans('common.confirm') }}</button>
                </div>
            </form>

        </div>
    </div>
@stop
