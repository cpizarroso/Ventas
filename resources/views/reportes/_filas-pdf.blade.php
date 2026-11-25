                @foreach ($ventas as $venta)
                    <tr>
                        <td>#{{ $venta->numeroTicket() }}</td>
                        <td>{{ $venta->created_at->format('d/m/Y') }}</td>
                        <td>{{ $venta->created_at->format('H:i') }}</td>
                        <td>{{ $venta->user?->name ?? '—' }}</td>
                        <td>{{ $venta->etiquetaTipoReporte() }}</td>
                        <td>{{ $venta->para_llevar ? 'Sí' : 'No' }}</td>
                        <td>{{ $venta->promocionesNombres() ?: '—' }}</td>
                        <td class="derecha">{{ $venta->detalles->sum('cantidad') }}</td>
                        <td>
                            @foreach ($venta->detalles as $detalle)
                                <span>{{ $detalle->cantidad }} × {{ $detalle->nombre }}</span>@if (! $loop->last), @endif
                            @endforeach
                        </td>
                        <td class="derecha">Bs {{ number_format((float) $venta->total, 2) }}</td>
                        <td>{{ $venta->metodo_pago?->etiqueta() ?? 'Efectivo' }}</td>
                        <td class="derecha">
                            {{ $venta->monto_recibido !== null ? number_format((float) $venta->monto_recibido, 2) : '—' }}
                        </td>
                    </tr>
                @endforeach
