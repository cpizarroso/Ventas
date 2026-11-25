                @foreach ($cajas as $caja)
                    <tr>
                        <td>#{{ $caja->id }}</td>
                        <td>{{ $caja->estado->etiqueta() }}</td>
                        <td>{{ $caja->user?->name ?? '—' }}</td>
                        <td>{{ $caja->fecha_apertura?->format('d/m/Y H:i') ?? '—' }}</td>
                        <td>{{ $caja->fecha_cierre?->format('d/m/Y H:i') ?? '—' }}</td>
                        <td class="derecha">Bs {{ number_format((float) $caja->monto_inicial, 2) }}</td>
                        <td class="derecha">{{ $caja->cantidad_ventas }}</td>
                        <td class="derecha">Bs {{ number_format((float) $caja->total_ventas, 2) }}</td>
                        <td class="derecha">Bs {{ number_format((float) $caja->total_efectivo, 2) }}</td>
                        <td class="derecha">Bs {{ number_format((float) $caja->total_qr, 2) }}</td>
                        <td class="derecha">Bs {{ number_format((float) $caja->diferencia, 2) }}</td>
                    </tr>
                @endforeach
