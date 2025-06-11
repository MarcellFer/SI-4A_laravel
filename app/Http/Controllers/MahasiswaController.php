<?php

namespace App\Http\Controllers;

use App\Models\Fakultas;
use App\Models\Prodi;
use App\Models\Mahasiswa;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class MahasiswaController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $mahasiswa = Mahasiswa::all();
        return view('mahasiswa.index', compact('mahasiswa'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $fakultas = Fakultas::all();
        $prodi = Prodi::all();
        return view('mahasiswa.create', compact('fakultas'), compact('prodi'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        // validasi input
        $input = $request->validate([
            'npm' => 'required|unique:mahasiswa',
            'nama' => 'required',
            'jk' => 'required',
            'tanggal_lahir' => 'required|date',
            'tempat_lahir' => 'required',
            'asal_sma' => 'required',
            'prodi_id' => 'required',
            'foto' => 'image|mimes:jpeg,png,jpg,gif|max:2048',
        ]);
        //upload foto
        if ($request->hasFile('foto')) {
            // $file = $request->file('foto'); // ambil file foto
            // $filename = time() . '.' . $file->getClientOriginalExtension();
            // $file->move(public_path('images'), $filename); // simpan foto ke folder public/images
            // $input['foto'] = $filename; // simpan nama file baru ke $input

            try {
                $file = $request->file('foto');
                $response = Http::asMultipart()->post(
                    'https://api.cloudinary.com/v1_1/' . env('CLOUDINARY_CLOUD_NAME') . '/image/upload',
                    [
                        [
                            'name'     => 'file',
                            'contents' => fopen($file->getRealPath(), 'r'),
                            'filename' => $file->getClientOriginalName(),
                        ],
                        [
                            'name'     => 'upload_preset',
                            'contents' => env('CLOUDINARY_UPLOAD_PRESET'),
                        ],
                    ]
                );

                $result = $response->json();
                if (isset($result['secure_url'])) {
                    $input['foto'] = $result['secure_url'];
                } else {
                    return back()->withErrors(['foto' => 'Cloudinary upload error: ' . ($result['error']['message'] ?? 'Unknown error')]);
                }
            } catch (\Exception $e) {
                return back()->withErrors(['foto' => 'Cloudinary error: ' . $e->getMessage()]);
            }
        }
        // simpan data ke tabel mahasiswa
        Mahasiswa::create($input);
        // redirect ke route mahasiswa.index
        return redirect()->route('mahasiswa.index')->with('success', 'mahasiswa berhasil ditambahkan.');
    }

    /**
     * Display the specified resource.
     */
    public function show(Mahasiswa $mahasiswa)
    {
        // dd($mahasiswa);
        return view('mahasiswa.show', compact('mahasiswa'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Mahasiswa $mahasiswa)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Mahasiswa $mahasiswa)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($mahasiswa)
    {
         $mahasiswa = 
        Mahasiswa::findOrFail
        ($mahasiswa);
        if($mahasiswa->foto) {
            $fotopath = public_path('images/'.$mahasiswa->foto);
            if(file_exists($fotopath)){
                unlink($fotopath);
            }
        }

        $mahasiswa->delete();

    return redirect()->route('mahasiswa.index')->with('success','mahasiswa berhasil dihapus.');
    }
}
