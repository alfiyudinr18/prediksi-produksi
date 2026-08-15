<div class="bg-white border-end shadow-sm vh-100"
     style="width:250px;">

    <div class="p-4 text-center border-bottom">

        <h5 class="fw-bold">

            Menu

        </h5>

    </div>

    <div class="list-group list-group-flush">

        <a href="{{ route('dashboard') }}"
           class="list-group-item list-group-item-action">

            <i class="bi bi-speedometer2 me-2"></i>

            Dashboard

        </a>

        <a href="{{ route('produksi.index') }}"
           class="list-group-item list-group-item-action">

            <i class="bi bi-table me-2"></i>

            Data Produksi

        </a>

        <a href="{{ route('training.index') }}"
           class="list-group-item list-group-item-action">

            <i class="bi bi-cpu me-2"></i>

            Training Model

        </a>

        <a href="{{ route('prediksi.index') }}"
           class="list-group-item list-group-item-action">

            <i class="bi bi-graph-up-arrow me-2"></i>

            Prediksi Produksi

        </a>

        <a href="{{ route('prediksi.history') }}"
           class="list-group-item list-group-item-action">

            <i class="bi bi-clock-history me-2"></i>

            Riwayat Prediksi

        </a>

    </div>

</div>
