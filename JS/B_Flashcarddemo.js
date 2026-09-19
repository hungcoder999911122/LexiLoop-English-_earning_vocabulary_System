$(function () {

    // Du lieu tu vung demo (chua can goi API/dang nhap)
    var B_Flashcarddemo_DanhSachThe = [
        { tu: "Airport", nghia: "Sân bay" },
        { tu: "Software", nghia: "Phần mềm" },
        { tu: "Passport", nghia: "Hộ chiếu" },
        { tu: "Noodle", nghia: "Mì" },
        { tu: "Meeting", nghia: "Cuộc họp" }
    ];

    var B_Flashcarddemo_ViTriHienTai = 0;
    var B_Flashcarddemo_DangLat = false;

    function B_Flashcarddemo_HienThi(viTri) {
        var the = B_Flashcarddemo_DanhSachThe[viTri];

        $("#B_Flashcarddemo_TuVung").text(the.tu);
        $("#B_Flashcarddemo_Nghia").text(the.nghia).hide();
        $("#B_Flashcarddemo_Goi").show();
        $("#B_Flashcarddemo_SoThe").text("Thẻ " + (viTri + 1) + "/" + B_Flashcarddemo_DanhSachThe.length + " (demo)");

        B_Flashcarddemo_DangLat = false;
    }

    function B_Flashcarddemo_KetThucDemo() {
        $("#B_Flashcarddemo_TuVung").text("Bạn đã học hết 5 thẻ demo!");
        $("#B_Flashcarddemo_Goi").hide();
        $("#B_Flashcarddemo_Nghia").text("Đăng ký để tiếp tục học không giới hạn.").show();
        $("#B_Flashcarddemo_SoThe").text("Hoàn thành demo");
        $("#B_Flashcarddemo_BtnChuaNho, #B_Flashcarddemo_BtnDaNho").prop("disabled", true);
    }

    // Nhan vao the de lat xem nghia
    $("#B_Flashcarddemo_The").on("click", function () {
        B_Flashcarddemo_DangLat = !B_Flashcarddemo_DangLat;

        if (B_Flashcarddemo_DangLat) {
            $("#B_Flashcarddemo_Nghia").show();
            $("#B_Flashcarddemo_Goi").hide();
        } else {
            $("#B_Flashcarddemo_Nghia").hide();
            $("#B_Flashcarddemo_Goi").show();
        }
    });

    // Nut Chua nho / Da nho: chuyen sang the tiep theo
    $("#B_Flashcarddemo_BtnChuaNho, #B_Flashcarddemo_BtnDaNho").on("click", function () {
        B_Flashcarddemo_ViTriHienTai++;

        if (B_Flashcarddemo_ViTriHienTai < B_Flashcarddemo_DanhSachThe.length) {
            B_Flashcarddemo_HienThi(B_Flashcarddemo_ViTriHienTai);
        } else {
            B_Flashcarddemo_KetThucDemo();
        }
    });

    // Khoi tao the dau tien
    B_Flashcarddemo_HienThi(B_Flashcarddemo_ViTriHienTai);

});
