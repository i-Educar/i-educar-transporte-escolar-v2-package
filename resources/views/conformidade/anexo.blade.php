<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $assunto }}</title>
    <style>
        html, body { height: 100%; margin: 0; }
        body { display: flex; flex-direction: column; background: #1a242b; color: #d5e0e6; font-family: "Segoe UI", sans-serif; }
        main { flex: 1; min-height: 0; background: #fff; }
        iframe, img, object { display: block; width: 100%; height: 100%; border: 0; object-fit: contain; background: #fff; }
        footer { flex: none; padding: 10px 16px; background: #15222b; border-top: 3px solid #0e7490; text-align: center; font-size: 13px; line-height: 1.45; }
        footer strong { color: #fff; font-weight: 600; }
    </style>
</head>
<body>
    <main>
        @if(str_starts_with($mime, 'image/'))
            <img src="{{ $url }}" alt="{{ $nome }}">
        @elseif($mime === 'application/pdf')
            <iframe src="{{ $url }}#toolbar=0&navpanes=0" title="{{ $nome }}"></iframe>
        @else
            <object data="{{ $url }}" type="{{ $mime }}" title="{{ $nome }}">A leitura deste formato fica nesta tela.</object>
        @endif
    </main>
    <footer>
        <div><strong>{{ $assunto }}</strong> · {{ $data }}</div>
        <div>Propriedade do sistema · {{ $municipio }} · Transporte escolar · i-Educar · Consulta interna, sem destinação externa</div>
    </footer>
</body>
</html>
