@php
    $tituloSocial = 'CAPIGUARA';
    $descripcionSocial = 'Sistema de venta para restaurantes de pollos y comida rápida.';
    $imagenSocial = asset('social-preview.png');
@endphp

<meta name="description" content="{{ $descripcionSocial }}">

<meta property="og:type" content="website">
<meta property="og:site_name" content="{{ $tituloSocial }}">
<meta property="og:title" content="{{ $tituloSocial }}">
<meta property="og:description" content="{{ $descripcionSocial }}">
<meta property="og:url" content="{{ url()->current() }}">
<meta property="og:image" content="{{ $imagenSocial }}">
<meta property="og:image:type" content="image/png">
<meta property="og:image:width" content="1200">
<meta property="og:image:height" content="630">
<meta property="og:image:alt" content="{{ $tituloSocial }}">

<meta name="twitter:card" content="summary_large_image">
<meta name="twitter:title" content="{{ $tituloSocial }}">
<meta name="twitter:description" content="{{ $descripcionSocial }}">
<meta name="twitter:image" content="{{ $imagenSocial }}">
<meta name="twitter:image:alt" content="{{ $tituloSocial }}">
