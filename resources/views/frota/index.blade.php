@extends('transporte-escolar::layout')

@section('transporte')
    <h1><i class="fa fa-bus" aria-hidden="true"></i> {{ $titulo }}</h1>
    <p class="lead">A tela mostra {{ \iEducar\Packages\BuritiTransporteEscolar\Support\Pagina::TAMANHO }} registros por vez. A planilha traz a lista inteira, para conferir.</p>
    <div data-painel>
        <div class="barra-dados">
            <label class="filtro"><i class="fa fa-search" aria-hidden="true"></i><input type="search" data-filtro-tabela placeholder="Buscar nesta página"></label>
            <a class="botao claro" href="{{ url('/transporte-escolar/'.$recurso.'/csv') }}"><i class="fa fa-download" aria-hidden="true"></i> Exportar</a>
            @if($podeGravar)
                <button type="button" class="botao" data-abre-modal="novo-registro"><i class="fa fa-plus" aria-hidden="true"></i> Incluir</button>
            @endif
        </div>
        <div class="tabela-wrap">
            <table>
                <thead>
                    <tr>
                        @if($recurso === 'veiculos')
                            <th>Placa</th><th>Descrição</th><th>Passageiros</th><th>Ativo</th><th></th>
                        @elseif($recurso === 'motoristas')
                            <th>Nome</th><th>Telefone</th><th>E-mail</th><th>Matrícula</th><th>CNH</th><th></th>
                        @elseif($recurso === 'monitores')
                            <th>Nome</th><th>Telefone</th><th>E-mail</th><th>Matrícula</th><th>Turno</th><th></th>
                        @elseif($recurso === 'empresas')
                            <th>Nome</th><th>CNPJ</th><th>Telefone</th><th>E-mail</th><th></th>
                        @elseif($recurso === 'fornecedores')
                            <th>Nome</th><th>Telefone</th><th>E-mail</th><th></th>
                        @else
                            <th>Ponto</th><th>Latitude</th><th>Longitude</th><th>Raio (m)</th><th></th>
                        @endif
                    </tr>
                </thead>
                <tbody>
                    @forelse($linhas as $linha)
                        @php
                            $textoBusca = [];
                            foreach ((array) $linha as $valor) {
                                if (is_scalar($valor) && $valor !== '') {
                                    $textoBusca[] = $valor;
                                }
                            }
                        @endphp
                        <tr data-busca="{{ implode(' ', $textoBusca) }}">
                            @if($recurso === 'veiculos')
                                <td>{{ $linha->placa ?: '—' }}</td>
                                <td>{{ $linha->descricao }}</td>
                                <td>{{ $linha->passageiros }}</td>
                                <td>{{ $linha->ativo ? 'Sim' : 'Não' }}</td>
                                <td class="acoes"><button type="button" class="icone ver" data-abre-modal="ver-{{ $loop->index }}" aria-label="Ver veículo" title="Ver"><i class="fa fa-eye" aria-hidden="true"></i></button>@if($podeGravar)<button type="button" class="icone editar" data-abre-modal="editar-{{ $linha->id }}" aria-label="Editar veículo" title="Editar"><i class="fa fa-pencil" aria-hidden="true"></i></button>@endif</td>
                            @elseif($recurso === 'motoristas')
                                <td>{{ $linha->nome }}</td>
                                <td>{{ $linha->telefone ?: '—' }}</td>
                                <td>{{ $linha->email ?: '—' }}</td>
                                <td>{{ $linha->matricula_funcional ?: '—' }}</td>
                                <td>{{ $linha->cnh ?: '—' }}</td>
                                <td class="acoes"><button type="button" class="icone ver" data-abre-modal="ver-{{ $loop->index }}" aria-label="Ver motorista" title="Ver"><i class="fa fa-eye" aria-hidden="true"></i></button>@if($podeGravar)<button type="button" class="icone editar" data-abre-modal="editar-{{ $linha->id }}" aria-label="Editar motorista" title="Editar"><i class="fa fa-pencil" aria-hidden="true"></i></button>@endif</td>
                            @elseif($recurso === 'monitores')
                                <td>{{ $linha->nome }}</td>
                                <td>{{ $linha->telefone ?: '—' }}</td>
                                <td>{{ $linha->email ?: '—' }}</td>
                                <td>{{ $linha->matricula_funcional ?: '—' }}</td>
                                <td>{{ $linha->turno ?: '—' }}</td>
                                <td class="acoes"><button type="button" class="icone ver" data-abre-modal="ver-{{ $loop->index }}" aria-label="Ver monitor" title="Ver"><i class="fa fa-eye" aria-hidden="true"></i></button>@if($podeGravar)<button type="button" class="icone editar" data-abre-modal="editar-{{ $linha->id }}" aria-label="Editar monitor" title="Editar"><i class="fa fa-pencil" aria-hidden="true"></i></button>@endif</td>
                            @elseif($recurso === 'empresas')
                                <td>{{ $linha->nome }}</td>
                                <td>{{ $linha->cnpj ?: '—' }}</td>
                                <td>{{ $linha->telefone ?: '—' }}</td>
                                <td>{{ $linha->email ?: '—' }}</td>
                                <td class="acoes"><button type="button" class="icone ver" data-abre-modal="ver-{{ $loop->index }}" aria-label="Ver empresa" title="Ver"><i class="fa fa-eye" aria-hidden="true"></i></button>@if($podeGravar)<button type="button" class="icone editar" data-abre-modal="editar-{{ $linha->id }}" aria-label="Editar empresa" title="Editar"><i class="fa fa-pencil" aria-hidden="true"></i></button>@endif</td>
                            @elseif($recurso === 'fornecedores')
                                <td>{{ $linha->nome }}</td>
                                <td>{{ $linha->telefone ?: '—' }}</td>
                                <td>{{ $linha->email ?: '—' }}</td>
                                <td class="acoes"><button type="button" class="icone ver" data-abre-modal="ver-{{ $loop->index }}" aria-label="Ver fornecedor" title="Ver"><i class="fa fa-eye" aria-hidden="true"></i></button>@if($podeGravar)<button type="button" class="icone editar" data-abre-modal="editar-{{ $linha->id }}" aria-label="Editar fornecedor" title="Editar"><i class="fa fa-pencil" aria-hidden="true"></i></button>@endif</td>
                            @else
                                <td>{{ $linha->descricao }}</td>
                                <td>{{ $linha->latitude ?? '—' }}</td>
                                <td>{{ $linha->longitude ?? '—' }}</td>
                                <td>{{ $linha->raio_metros ?? '—' }}</td>
                                <td class="acoes">
                                    @if($podeGravar)
                                        <button type="button" class="icone editar" data-abre-modal="ponto-{{ $linha->id }}" aria-label="Editar ponto" title="Editar"><i class="fa fa-pencil" aria-hidden="true"></i></button>
                                    @endif
                                    @if($podeExcluir)
                                        <form method="post" action="{{ url('/transporte-escolar/pontos/'.$linha->id.'/excluir') }}">
                                            @csrf
                                            <button type="submit" class="icone cancelar" aria-label="Excluir ponto" title="Excluir"><i class="fa fa-trash" aria-hidden="true"></i></button>
                                        </form>
                                    @endif
                                </td>
                            @endif
                        </tr>
                    @empty
                        <tr data-vazio><td colspan="5" class="vazio">Nenhum registro.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @include('transporte-escolar::paginacao', [
            'url' => url('/transporte-escolar/'.$recurso),
            'consulta' => [],
        ])
    </div>

    @if($podeGravar)
        <dialog class="modal" id="novo-registro">
            <div class="modal-cabeca">
                <h2>Incluir {{ mb_strtolower($titulo) }}</h2>
                <button type="button" data-fecha-modal aria-label="Fechar">&times;</button>
            </div>
            <form method="post">
                @csrf
                <input type="hidden" name="recurso" value="{{ $recurso }}">
                @if(in_array($recurso, ['empresas', 'fornecedores']))
                    <label>CNPJ ou CPF <input name="cnpj" required></label>
                    <label>Telefone <input name="telefone"></label>
                    <label>E-mail <input name="email" type="email"></label>
                    <label>Observação <input name="observacao"></label>
                @elseif($recurso === 'motoristas')
                    <label>CPF <input name="cpf" required></label>
                    <div class="linha">
                        <label>Matrícula funcional <input name="matricula_funcional"></label>
                        <label>Telefone <input name="telefone"></label>
                    </div>
                    <label>E-mail <input name="email" type="email"></label>
                    <div class="linha">
                        <label>CNH <input name="cnh"></label>
                        <label>Categoria <input name="tipo_cnh"></label>
                    </div>
                    <div class="linha">
                        <label>Habilitação <input type="date" name="dt_habilitacao"></label>
                        <label>Vencimento <input type="date" name="vencimento_cnh"></label>
                    </div>
                    <label>Curso de transporte escolar <input type="date" name="curso_escolar_validade"></label>
                    <label>Vínculo <select name="vinculo">@foreach($codigos::VINCULO as $codigo => $rotulo)<option value="{{ $codigo }}">{{ $rotulo }}</option>@endforeach</select></label>
                    <div class="linha">
                        <label>Turno <input name="turno"></label>
                        <label>Salário mensal <input name="salario_mensal"></label>
                    </div>
                    <div class="linha">
                        <label>Encargo % <input name="encargo_percent"></label>
                        <label>Carga horária <input name="carga_horaria"></label>
                    </div>
                @elseif($recurso === 'monitores')
                    <label>CPF <input name="cpf" required></label>
                    <div class="linha">
                        <label>Matrícula funcional <input name="matricula_funcional"></label>
                        <label>Telefone <input name="telefone"></label>
                    </div>
                    <label>E-mail <input name="email" type="email"></label>
                    <div class="linha">
                        <label>Turno <input name="turno"></label>
                        <label>Salário mensal <input name="salario_mensal"></label>
                    </div>
                    <div class="linha">
                        <label>Encargo % <input name="encargo_percent"></label>
                        <label>Carga horária <input name="carga_horaria"></label>
                    </div>
                @elseif($recurso === 'veiculos')
                    @include('transporte-escolar::frota.campos-veiculo')
                @else
                    <label>Descrição do ponto <input name="descricao" required maxlength="70"></label>
                    <div class="linha">
                        <label>Latitude <input name="latitude" placeholder="-30.0277000"></label>
                        <label>Longitude <input name="longitude" placeholder="-51.2287000"></label>
                    </div>
                    <label>Raio da cerca (metros) <input name="raio_metros" type="number" min="1" max="500" placeholder="150"></label>
                    <p class="lead">Latitude e longitude entram juntas. Sem as duas, o ponto fica sem mapa.</p>
                @endif
                <button type="submit">Gravar</button>
            </form>
        </dialog>
        @if($recurso === 'pontos')
            @foreach($linhas as $linha)
                <dialog class="modal" id="ponto-{{ $linha->id }}">
                    <div class="modal-cabeca">
                        <h2>Editar ponto</h2>
                        <button type="button" data-fecha-modal aria-label="Fechar">&times;</button>
                    </div>
                    <form method="post" action="{{ url('/transporte-escolar/pontos/'.$linha->id) }}">
                        @csrf
                        <label>Descrição <input name="descricao" value="{{ $linha->descricao }}" required maxlength="70"></label>
                        <div class="linha">
                            <label>Latitude <input name="latitude" value="{{ $linha->latitude }}"></label>
                            <label>Longitude <input name="longitude" value="{{ $linha->longitude }}"></label>
                        </div>
                        <label>Raio da cerca (metros) <input name="raio_metros" type="number" min="1" max="500" value="{{ $linha->raio_metros }}"></label>
                        <button type="submit">Atualizar</button>
                    </form>
                </dialog>
            @endforeach
        @endif
    @endif
@foreach($linhas as $linha)
        <dialog class="modal" id="ver-{{ $loop->index }}">
            <div class="modal-cabeca">
                <h2>Dados do registro</h2>
                <button type="button" data-fecha-modal aria-label="Fechar">&times;</button>
            </div>
            <div class="modal-corpo">
                <dl class="ficha">
                    @foreach((array) $linha as $campo => $valor)
                        @continue(in_array($campo, ['id', 'created_at', 'updated_at', 'origem', 'origem_chave', 'ref_idpes'], true) || str_ends_with($campo, '_id'))
                        @php
                            $rotuloCampo = ['nome' => 'Nome', 'cnpj' => 'CNPJ', 'observacao' => 'Observação', 'email' => 'E-mail', 'telefone' => 'Telefone', 'matricula_funcional' => 'Matrícula funcional', 'cnh' => 'CNH', 'tipo_cnh' => 'Categoria', 'vencimento_cnh' => 'Vencimento da CNH', 'curso_escolar_validade' => 'Curso de transporte escolar', 'vinculo' => 'Vínculo', 'salario_mensal' => 'Salário', 'turno' => 'Turno', 'encargo_percent' => 'Encargo %', 'carga_horaria' => 'Carga horária', 'placa' => 'Placa', 'descricao' => 'Descrição', 'renavam' => 'RENAVAM', 'chassi' => 'Chassi', 'marca' => 'Marca', 'ano_fabricacao' => 'Ano de fabricação', 'ano_modelo' => 'Ano do modelo', 'passageiros' => 'Passageiros', 'tipo_censo' => 'Tipo no Censo', 'malha' => 'Malha', 'adaptado_pcd' => 'Adaptado', 'exclusivo_escolar' => 'Exclusivo escolar', 'caminho_da_escola' => 'Caminho da Escola', 'consumo_medio_km_l' => 'Consumo km/l', 'qtd_pneus' => 'Pneus', 'ipva_vencimento' => 'IPVA', 'seguro_vencimento' => 'Seguro', 'licenciamento_vencimento' => 'Licenciamento', 'vistoria_vencimento' => 'Vistoria', 'ativo' => 'Ativo', 'latitude' => 'Latitude', 'longitude' => 'Longitude', 'raio_metros' => 'Raio (m)'][$campo] ?? null;
                        @endphp
                        @continue($rotuloCampo === null)
                        <div>
                            <dt>{{ $rotuloCampo }}</dt>
                            <dd>
                                @if($campo === 'tipo_censo')
                                    {{ $codigos::VEICULO_CENSO[(int) $valor] ?? ($valor ?: '—') }}
                                @elseif($campo === 'malha')
                                    {{ $codigos::MALHA[$valor] ?? ($valor ?: '—') }}
                                @elseif($campo === 'vinculo')
                                    {{ $codigos::VINCULO[$valor] ?? ($valor ?: '—') }}
                                @elseif(in_array($campo, ['ativo', 'adaptado_pcd', 'caminho_da_escola', 'exclusivo_escolar'], true))
                                    {{ $valor ? 'Sim' : 'Não' }}
                                @else
                                    {{ $valor === null || $valor === '' ? '—' : $valor }}
                                @endif
                            </dd>
                        </div>
                    @endforeach
                </dl>
            </div>
        </dialog>
    @endforeach
    @if($podeGravar && $recurso !== 'pontos')
        @foreach($linhas as $linha)
            <dialog class="modal" id="editar-{{ $linha->id }}">
                <div class="modal-cabeca"><h2>Editar</h2><button type="button" data-fecha-modal aria-label="Fechar">&times;</button></div>
                <form method="post" action="{{ url('/transporte-escolar/'.$recurso.'/'.$linha->id) }}">
                    @csrf
                    @if(in_array($recurso, ['empresas', 'fornecedores'], true))
                        <label>Telefone <input name="telefone" value="{{ $linha->telefone }}"></label>
                        <label>E-mail <input name="email" type="email" value="{{ $linha->email }}"></label>
                        <label>Observação <input name="observacao" value="{{ $linha->observacao }}"></label>
                    @elseif($recurso === 'motoristas')
                        <div class="linha">
                            <label>Matrícula funcional <input name="matricula_funcional" value="{{ $linha->matricula_funcional }}"></label>
                            <label>Telefone <input name="telefone" value="{{ $linha->telefone }}"></label>
                        </div>
                        <label>E-mail <input name="email" type="email" value="{{ $linha->email }}"></label>
                        <div class="linha">
                            <label>CNH <input name="cnh" value="{{ $linha->cnh }}"></label>
                            <label>Categoria <input name="tipo_cnh" value="{{ $linha->tipo_cnh }}"></label>
                        </div>
                        <div class="linha">
                            <label>Habilitação <input type="date" name="dt_habilitacao" value="{{ $linha->dt_habilitacao }}"></label>
                            <label>Vencimento <input type="date" name="vencimento_cnh" value="{{ $linha->vencimento_cnh }}"></label>
                            <label>Curso de transporte escolar <input type="date" name="curso_escolar_validade" value="{{ $linha->curso_escolar_validade }}"></label>
                        </div>
                        <label>Vínculo <select name="vinculo">@foreach($codigos::VINCULO as $codigo => $rotulo)<option value="{{ $codigo }}" @selected($linha->vinculo === $codigo)>{{ $rotulo }}</option>@endforeach</select></label>
                        <div class="linha">
                            <label>Turno <input name="turno" value="{{ $linha->turno }}"></label>
                            <label>Salário mensal <input name="salario_mensal" value="{{ $linha->salario_mensal }}"></label>
                        </div>
                        <div class="linha">
                            <label>Encargo % <input name="encargo_percent" value="{{ $linha->encargo_percent }}"></label>
                            <label>Carga horária <input name="carga_horaria" value="{{ $linha->carga_horaria }}"></label>
                        </div>
                    @elseif($recurso === 'monitores')
                        <div class="linha">
                            <label>Matrícula funcional <input name="matricula_funcional" value="{{ $linha->matricula_funcional }}"></label>
                            <label>Telefone <input name="telefone" value="{{ $linha->telefone }}"></label>
                        </div>
                        <label>E-mail <input name="email" type="email" value="{{ $linha->email }}"></label>
                        <div class="linha">
                            <label>Turno <input name="turno" value="{{ $linha->turno }}"></label>
                            <label>Salário mensal <input name="salario_mensal" value="{{ $linha->salario_mensal }}"></label>
                        </div>
                        <div class="linha">
                            <label>Encargo % <input name="encargo_percent" value="{{ $linha->encargo_percent }}"></label>
                            <label>Carga horária <input name="carga_horaria" value="{{ $linha->carga_horaria }}"></label>
                        </div>
                    @else
                        @include('transporte-escolar::frota.campos-veiculo', ['veiculo' => $linha])
                    @endif
                    <button type="submit">Atualizar</button>
                </form>
            </dialog>
        @endforeach
    @endif
@endsection
