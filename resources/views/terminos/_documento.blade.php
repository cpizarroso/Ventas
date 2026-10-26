@php
    $fechaVigencia = \Illuminate\Support\Carbon::parse($vigenteDesde)->format('d/m/Y');
@endphp

<article class="space-y-8 text-sm leading-7 text-stone-700 dark:text-stone-300">
    <header class="border-b border-stone-200 pb-6 dark:border-stone-700">
        <p class="text-xs font-semibold uppercase tracking-[0.2em] text-amber-700 dark:text-amber-400">Documento legal</p>
        <h1 class="mt-2 text-2xl font-semibold leading-tight text-stone-900 sm:text-3xl dark:text-stone-100">
            Términos y Condiciones y Política de Privacidad
        </h1>
        <p class="mt-3 text-sm text-stone-500 dark:text-stone-400">
            Versión {{ $version }} · Vigente desde el {{ $fechaVigencia }}
        </p>
        <p class="mt-4">
            Este documento regula el acceso y uso de JIBAS, el servicio de gestión de ventas operado por
            {{ $titular }}. Léelo completo antes de aceptar. Si no estás de acuerdo, no debes usar el servicio.
        </p>
    </header>

    <section aria-labelledby="titular">
        <h2 id="titular" class="text-lg font-semibold text-stone-900 dark:text-stone-100">1. Identificación del titular</h2>
        <p class="mt-2">
            JIBAS es un software operado por <strong>{{ $titular }}</strong>, con contacto en
            <a href="mailto:{{ $contacto }}" class="font-medium text-amber-700 underline decoration-amber-300 underline-offset-4 hover:text-amber-800 dark:text-amber-400 dark:decoration-amber-700 dark:hover:text-amber-300">{{ $contacto }}</a>,
            ciudad de {{ $ciudad }}, {{ $pais }}. El negocio que utiliza una instancia de JIBAS es el
            <strong>negocio usuario</strong> y es responsable de su instancia, de sus usuarios, de la información comercial
            que carga y de las imágenes que sube.
        </p>
    </section>

    <section aria-labelledby="aceptacion">
        <h2 id="aceptacion" class="text-lg font-semibold text-stone-900 dark:text-stone-100">2. Aceptación y capacidad</h2>
        <p class="mt-2">
            Al marcar la casilla y presionar “Aceptar y continuar”, el usuario declara haber leído y aceptado estos
            Términos y la Política de Privacidad, en su nombre y en representación del negocio. Declara ser mayor de edad y
            tener facultades suficientes para obligar al negocio. La aceptación se registra con fecha, hora, dirección IP,
            navegador y versión del documento.
        </p>
        <p class="mt-2">
            La aceptación electrónica tiene la misma validez que una firma manuscrita en la medida permitida por la ley.
            Si el usuario no acepta, no podrá acceder a las funciones internas de JIBAS.
        </p>
    </section>

    <section aria-labelledby="servicio">
        <h2 id="servicio" class="text-lg font-semibold text-stone-900 dark:text-stone-100">3. Naturaleza del servicio</h2>
        <p class="mt-2">
            JIBAS es una herramienta de gestión de ventas, inventario, tickets y reportes. No es un banco, pasarela de
            pagos, sistema contable oficial, servicio de facturación electrónica ni asesoría legal, contable o tributaria.
            El negocio usuario debe verificar sus cálculos, impuestos, tickets y reportes antes de usarlos para cualquier
            obligación legal o comercial.
        </p>
        <p class="mt-2">
            El servicio es de pago. El alcance, precio y condiciones comerciales de la licencia o suscripción se rigen por
            la propuesta, contrato o comprobante de pago aplicable. La falta de pago puede generar la suspensión del acceso.
        </p>
    </section>

    <section aria-labelledby="vibe-coding">
        <h2 id="vibe-coding" class="text-lg font-semibold text-stone-900 dark:text-stone-100">4. Vibe coding y limitación por uso de inteligencia artificial</h2>
        <p class="mt-2">
            Se informa expresamente que el software fue desarrollado con asistencia de herramientas de inteligencia
            artificial generativa, práctica conocida como <strong>vibe coding</strong>. El titular no garantiza que el
            software esté libre de errores, vulnerabilidades, fallos de cálculo, omisiones o comportamientos no previstos.
            El usuario debe realizar pruebas, verificar la información y mantener respaldos propios.
        </p>
        <p class="mt-2">
            La mención de vibe coding se refiere al desarrollo del software y no implica que los datos de los usuarios sean
            procesados por inteligencia artificial en tiempo de ejecución, salvo que una funcionalidad lo indique
            expresamente. El usuario no debe usar JIBAS como único sistema para cumplir obligaciones legales, contables o
            tributarias.
        </p>
    </section>

    <section aria-labelledby="cuentas">
        <h2 id="cuentas" class="text-lg font-semibold text-stone-900 dark:text-stone-100">5. Cuentas y seguridad</h2>
        <p class="mt-2">
            Las cuentas son creadas por administradores del negocio usuario. El usuario debe mantener sus credenciales
            confidenciales, no compartirlas y notificar de inmediato cualquier acceso no autorizado. El negocio usuario
            responde por el uso que hagan sus usuarios y por la configuración de permisos que asigne.
        </p>
        <p class="mt-2">
            El titular puede suspender cuentas cuando detecte riesgos de seguridad, uso fraudulento o incumplimiento de
            estos términos.
        </p>
    </section>

    <section aria-labelledby="uso-aceptable">
        <h2 id="uso-aceptable" class="text-lg font-semibold text-stone-900 dark:text-stone-100">6. Uso aceptable</h2>
        <p class="mt-2">Queda prohibido usar JIBAS para:</p>
        <ul class="mt-2 list-disc space-y-1 pl-5">
            <li>Fines ilícitos o contrarios a la ley aplicable.</li>
            <li>Infringir derechos de propiedad intelectual, privacidad o imagen de terceros.</li>
            <li>Subir malware, archivos maliciosos o contenido diseñado para dañar sistemas.</li>
            <li>Intentar acceder a cuentas, datos o sistemas sin autorización.</li>
            <li>Extraer datos de forma masiva, automatizada o no autorizada.</li>
            <li>Interferir con el funcionamiento del servicio o de sus proveedores.</li>
            <li>Suplantar identidades o realizar fraude, acoso, discriminación o contenido ilegal.</li>
        </ul>
    </section>

    <section aria-labelledby="imagenes">
        <h2 id="imagenes" class="text-lg font-semibold text-stone-900 dark:text-stone-100">7. Imágenes y contenidos subidos por el usuario</h2>
        <p class="mt-2">
            Cada usuario es el <strong>único responsable</strong> de las imágenes y contenidos que suba, incluidos logos,
            imágenes de productos, comprobantes y códigos QR. El usuario declara tener los derechos, licencias,
            autorizaciones y consentimientos necesarios para subirlos y para que sean mostrados dentro del servicio.
        </p>
        <p class="mt-2">
            Prohíbese subir contenido ilícito, difamatorio, obsceno, violento, fraudulento, que infrinja derechos de autor
            o que contenga datos sensibles de terceros sin autorización. El titular no revisa ni avala las imágenes subidas
            y puede eliminar cualquier contenido que incumpla estos términos, sin previo aviso.
        </p>
        <p class="mt-2">
            El usuario otorga a {{ $titular }} una licencia limitada, no exclusiva y revocable para almacenar, reproducir,
            mostrar y respaldar esos contenidos mientras sea necesario para operar el servicio. Las imágenes se almacenan
            en un disco público y pueden ser accedidas mediante URL; el usuario no debe subir información confidencial.
        </p>
        <p class="mt-2">
            El usuario mantendrá indemne a {{ $titular }} frente a reclamos, sanciones o daños de terceros derivados de las
            imágenes o contenidos que haya subido.
        </p>
    </section>

    <section aria-labelledby="qr-pagos">
        <h2 id="qr-pagos" class="text-lg font-semibold text-stone-900 dark:text-stone-100">8. QR, pagos, ventas e impuestos</h2>
        <p class="mt-2">
            El QR de pago es una imagen proporcionada por el usuario. JIBAS únicamente la muestra; no procesa pagos, no
            verifica transferencias, no confirma comprobantes y no responde por datos bancarios erróneos, fraudes,
            transferencias mal realizadas o comprobantes falsos. El negocio usuario es responsable de verificar los pagos,
            emitir facturas, declarar impuestos y cumplir sus obligaciones tributarias.
        </p>
    </section>

    <section aria-labelledby="propiedad-intelectual">
        <h2 id="propiedad-intelectual" class="text-lg font-semibold text-stone-900 dark:text-stone-100">9. Propiedad intelectual</h2>
        <p class="mt-2">
            {{ $titular }} conserva los derechos sobre el software, marca, diseño, documentación y mejoras de JIBAS. Se
            otorga al usuario una licencia limitada, no exclusiva, no transferible y revocable para usar el servicio
            conforme a estos términos. El usuario conserva sus contenidos; las sugerencias o comentarios pueden ser usados
            por el titular sin obligación de compensación.
        </p>
    </section>

    <section aria-labelledby="privacidad">
        <h2 id="privacidad" class="text-lg font-semibold text-stone-900 dark:text-stone-100">10. Privacidad y datos personales</h2>
        <p class="mt-2">
            JIBAS puede recopilar datos de identificación y contacto, credenciales, rol, ventas, productos, imágenes,
            registros de acceso, dirección IP y datos de aceptación de términos. Estos datos se usan para operar el
            servicio, autenticar usuarios, generar reportes, brindar soporte, mantener la seguridad y cumplir obligaciones
            legales.
        </p>
        <p class="mt-2">
            No se venden datos personales. Los datos pueden compartirse con proveedores de hosting, respaldo, bases de
            datos o pagos, y con autoridades cuando exista obligación legal. Se conservarán mientras la cuenta esté activa
            y durante los plazos legales aplicables.
        </p>
        <p class="mt-2">
            Respecto de los datos de ventas, clientes y contenidos que el negocio usuario carga, el negocio es responsable
            de su exactitud y de contar con la base legal para tratarlos. {{ $titular }} actúa como proveedor tecnológico y
            trata esos datos para prestar el servicio.
        </p>
        <p class="mt-2">
            El usuario puede solicitar acceso, rectificación, eliminación u oposición escribiendo a
            <a href="mailto:{{ $contacto }}" class="font-medium text-amber-700 underline decoration-amber-300 underline-offset-4 hover:text-amber-800 dark:text-amber-400 dark:decoration-amber-700 dark:hover:text-amber-300">{{ $contacto }}</a>.
            Cuando resulte aplicable, se respetarán los derechos irrenunciables de los consumidores y las normas
            obligatorias de protección de datos.
        </p>
    </section>

    <section aria-labelledby="cookies">
        <h2 id="cookies" class="text-lg font-semibold text-stone-900 dark:text-stone-100">11. Cookies y almacenamiento local</h2>
        <p class="mt-2">
            JIBAS usa cookies de sesión y protección CSRF, y almacenamiento local para preferencias como el tema visual.
            No se usan cookies publicitarias ni analítica de terceros, salvo que se informe expresamente.
        </p>
    </section>

    <section aria-labelledby="terceros">
        <h2 id="terceros" class="text-lg font-semibold text-stone-900 dark:text-stone-100">12. Terceros y disponibilidad</h2>
        <p class="mt-2">
            El servicio puede depender de hosting, base de datos, proveedores de pago o QR y servicios de terceros. El
            titular no controla esos servicios y no garantiza disponibilidad ininterrumpida. Puede haber mantenimientos,
            interrupciones, fallos de red o casos de fuerza mayor.
        </p>
    </section>

    <section aria-labelledby="garantias">
        <h2 id="garantias" class="text-lg font-semibold text-stone-900 dark:text-stone-100">13. Renuncia de garantías</h2>
        <p class="mt-2">
            El software se proporciona “tal cual” y “según disponibilidad”. No se garantiza que esté libre de errores, que
            sea ininterrumpido, que sea compatible con todos los dispositivos o que los resultados comerciales sean los
            esperados. El usuario debe verificar la información y mantener respaldos.
        </p>
    </section>

    <section aria-labelledby="responsabilidad">
        <h2 id="responsabilidad" class="text-lg font-semibold text-stone-900 dark:text-stone-100">14. Limitación de responsabilidad</h2>
        <p class="mt-2">
            En la máxima medida permitida por la ley, {{ $titular }} no será responsable por daños indirectos, lucro
            cesante, pérdida de datos, errores del usuario, imágenes subidas, QR mal configurados, incumplimientos
            tributarios o decisiones comerciales basadas en el software.
        </p>
        <p class="mt-2">
            La responsabilidad total del titular se limitará al monto efectivamente pagado por la licencia o suscripción de
            JIBAS durante los doce meses anteriores al hecho que origine el reclamo. Si el pago fue único, se tomará el
            monto pagado por la licencia. Nada de esto excluye derechos irrenunciables reconocidos por ley.
        </p>
    </section>

    <section aria-labelledby="indemnizacion">
        <h2 id="indemnizacion" class="text-lg font-semibold text-stone-900 dark:text-stone-100">15. Indemnización</h2>
        <p class="mt-2">
            El usuario indemnizará y mantendrá indemne a {{ $titular }} frente a reclamos, sanciones, daños y costos
            —incluidos honorarios legales— derivados de sus imágenes, contenidos, uso indebido, incumplimiento de estos
            términos o violación de derechos de terceros.
        </p>
    </section>

    <section aria-labelledby="terminacion">
        <h2 id="terminacion" class="text-lg font-semibold text-stone-900 dark:text-stone-100">16. Suspensión y terminación</h2>
        <p class="mt-2">
            El titular puede suspender o terminar cuentas por incumplimiento, uso fraudulento, riesgo de seguridad o
            requerimiento legal. El negocio usuario podrá solicitar la exportación de sus datos antes de la eliminación,
            cuando sea técnicamente posible. Las obligaciones de responsabilidad, indemnización y propiedad intelectual
            sobreviven a la terminación.
        </p>
    </section>

    <section aria-labelledby="cambios">
        <h2 id="cambios" class="text-lg font-semibold text-stone-900 dark:text-stone-100">17. Cambios en los términos</h2>
        <p class="mt-2">
            El titular puede modificar estos términos y publicará la nueva versión con su fecha de vigencia. Los cambios
            materiales requerirán una nueva aceptación. El uso continuado después de la entrada en vigor implica
            aceptación.
        </p>
    </section>

    <section aria-labelledby="ley">
        <h2 id="ley" class="text-lg font-semibold text-stone-900 dark:text-stone-100">18. Ley aplicable y jurisdicción</h2>
        <p class="mt-2">
            Estos términos se rigen por las leyes de {{ $pais }}. Las controversias se someterán a los tribunales de
            {{ $ciudad }}, {{ $pais }}, salvo norma obligatoria en contrario. Si alguna cláusula resulta inexigible, se
            limitará al máximo permitido y el resto seguirá vigente.
        </p>
    </section>

    <section aria-labelledby="disposiciones">
        <h2 id="disposiciones" class="text-lg font-semibold text-stone-900 dark:text-stone-100">19. Aplicación global y disposiciones generales</h2>
        <p class="mt-2">
            Estos términos se aplican a usuarios de cualquier país. El usuario es responsable de verificar que el uso de
            JIBAS cumpla las leyes de su jurisdicción. El documento constituye el acuerdo completo; la falta de ejercicio
            de un derecho no implica renuncia; las obligaciones pueden cederse solo con consentimiento; los encabezados
            son solo informativos.
        </p>
    </section>

    <section aria-labelledby="contacto">
        <h2 id="contacto" class="text-lg font-semibold text-stone-900 dark:text-stone-100">20. Contacto</h2>
        <p class="mt-2">
            Para consultas legales, reclamos o ejercicio de derechos, escribe a
            <a href="mailto:{{ $contacto }}" class="font-medium text-amber-700 underline decoration-amber-300 underline-offset-4 hover:text-amber-800 dark:text-amber-400 dark:decoration-amber-700 dark:hover:text-amber-300">{{ $contacto }}</a>
            o contacta a {{ $titular }} en {{ $ciudad }}, {{ $pais }}.
        </p>
    </section>
</article>
