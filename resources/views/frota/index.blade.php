@extends('transporte-escolar::layout')

@section('transporte')
    <h1>{{ $titulo }}</h1>
    <p>Nome, CPF, CNPJ e endereço continuam no cadastro de pessoas. Aqui fica só o que o núcleo não tem: CNH, salário de composição de custo, lotação, vencimentos e ponto de parada.</p>
    <ul>
        @foreach($linhas as $linha)
            <li>
                @foreach((array) $linha as $campo => $valor)
                    @if($valor !== null && $valor !== '') {{ $campo }}: {{ is_bool($valor) ? ($valor ? 'sim' : 'não') : $valor }} · @endif
                @endforeach
            </li>
        @endforeach
    </ul>

    @if($podeGravar)
        <form method="post">
            @csrf
            <input type="hidden" name="recurso" value="{{ $recurso }}">
            @if(in_array($recurso, ['empresas', 'fornecedores']))
                <p><label>CNPJ ou CPF <input name="cnpj" required></label></p>
                <p><label>Observação <input name="observacao"></label></p>
            @elseif($recurso === 'motoristas')
                <p><label>CPF <input name="cpf" required></label></p>
                <p><label>CNH <input name="cnh"></label> <label>Categoria <input name="tipo_cnh" size="3"></label></p>
                <p><label>Habilitação <input type="date" name="dt_habilitacao"></label> <label>Vencimento <input type="date" name="vencimento_cnh"></label></p>
                <p><label>Vínculo <select name="vinculo">@foreach($codigos::VINCULO as $codigo => $rotulo)<option value="{{ $codigo }}">{{ $rotulo }}</option>@endforeach</select></label></p>
                <p><label>Turno <input name="turno"></label> <label>Salário mensal <input name="salario_mensal"></label> <label>Encargo % <input name="encargo_percent"></label> <label>Carga horária <input name="carga_horaria"></label></p>
            @elseif($recurso === 'monitores')
                <p><label>CPF <input name="cpf" required></label></p>
                <p><label>Turno <input name="turno"></label> <label>Salário mensal <input name="salario_mensal"></label> <label>Encargo % <input name="encargo_percent"></label> <label>Carga horária <input name="carga_horaria"></label></p>
            @elseif($recurso === 'veiculos')
                <p><label>Descrição <input name="descricao" required></label> <label>Placa <input name="placa"></label></p>
                <p><label>RENAVAM <input name="renavam"></label> <label>Passageiros <input name="passageiros" required></label></p>
                <p><label>Tipo Censo <select name="tipo_censo">@foreach($codigos::VEICULO_CENSO as $codigo => $rotulo)<option value="{{ $codigo }}">{{ $rotulo }}</option>@endforeach</select></label></p>
                <p><label>Malha <select name="malha">@foreach($codigos::MALHA as $codigo => $rotulo)<option value="{{ $codigo }}">{{ $rotulo }}</option>@endforeach</select></label></p>
                <p><label><input type="checkbox" name="adaptado_pcd" value="1"> Adaptado</label> <label><input type="checkbox" name="caminho_da_escola" value="1"> Caminho da Escola</label></p>
                <p><label>Consumo km/l <input name="consumo_medio_km_l"></label> <label>Pneus <input name="qtd_pneus"></label></p>
                <p><label>IPVA <input type="date" name="ipva_vencimento"></label> <label>Seguro <input type="date" name="seguro_vencimento"></label></p>
                <p><label>Licenciamento <input type="date" name="licenciamento_vencimento"></label> <label>Vistoria <input type="date" name="vistoria_vencimento"></label></p>
            @else
                <p><label>Descrição do ponto <input name="descricao" required></label></p>
            @endif
            <button type="submit">Gravar</button>
        </form>
    @endif
@endsection
