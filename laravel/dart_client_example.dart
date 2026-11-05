import 'dart:convert';
import 'package:http/http.dart' as http;

/// Client SDK for the BuildEstimate AI Laravel REST API
class ConstructionApiClient {
  final String baseUrl;
  final String? groqApiKey;
  final http.Client _httpClient;

  ConstructionApiClient({
    this.baseUrl = 'http://localhost:8000/api/v1',
    this.groqApiKey,
    http.Client? httpClient,
  }) : _httpClient = httpClient ?? http.Client();

  Map<String, String> get _headers => {
        'Content-Type': 'application/json',
        'Accept': 'application/json',
      };

  /// Health check
  Future<Map<String, dynamic>> checkHealth() async {
    final response = await _httpClient.get(
      Uri.parse('$baseUrl/health'),
      headers: _headers,
    );
    return jsonDecode(response.body);
  }

  /// AI Chat with Autonomous Tool Execution
  Future<Map<String, dynamic>> chat({
    required List<Map<String, String>> messages,
    String? model,
  }) async {
    final body = {
      'messages': messages,
      if (groqApiKey != null) 'api_key': groqApiKey,
      if (model != null) 'model': model,
    };

    final response = await _httpClient.post(
      Uri.parse('$baseUrl/chat'),
      headers: _headers,
      body: jsonEncode(body),
    );
    return jsonDecode(response.body);
  }

  /// Room-based Residential House Estimation
  Future<Map<String, dynamic>> estimateByRooms({
    int rooms = 3,
    int toilets = 3,
    int balconies = 1,
    int floors = 1,
    bool includeLivingDining = true,
    bool includeKitchen = true,
    bool seismicZoneV = true,
  }) async {
    final body = {
      'rooms': rooms,
      'toilets': toilets,
      'balconies': balconies,
      'floors': floors,
      'include_living_dining': includeLivingDining,
      'include_kitchen': includeKitchen,
      'seismic_zone_v': seismicZoneV,
    };

    final response = await _httpClient.post(
      Uri.parse('$baseUrl/estimate/by-rooms'),
      headers: _headers,
      body: jsonEncode(body),
    );
    return jsonDecode(response.body);
  }

  /// Built-up Area Residential Estimation (IS 456 & IS 13920)
  Future<Map<String, dynamic>> estimateResidential({
    required double builtUpAreaSqft,
    int floors = 1,
    bool seismicZoneV = true,
  }) async {
    final body = {
      'built_up_area_sqft': builtUpAreaSqft,
      'floors': floors,
      'seismic_zone_v': seismicZoneV,
    };

    final response = await _httpClient.post(
      Uri.parse('$baseUrl/estimate/residential'),
      headers: _headers,
      body: jsonEncode(body),
    );
    return jsonDecode(response.body);
  }

  /// Rebar Schedule (Standard 12-meter commercial rods & bundles)
  Future<Map<String, dynamic>> estimateRebarSchedule({
    required double totalSteelKg,
    String application = 'residential',
  }) async {
    final body = {
      'total_steel_kg': totalSteelKg,
      'application': application,
    };

    final response = await _httpClient.post(
      Uri.parse('$baseUrl/estimate/rebar-schedule'),
      headers: _headers,
      body: jsonEncode(body),
    );
    return jsonDecode(response.body);
  }

  /// Bridge Bored Cast-in-Situ Piles & Lacey Scour Depth
  Future<Map<String, dynamic>> estimateBridgePile({
    required double peakDischargeQ,
    double laceySiltFactorF = 1.0,
    double pileDiameterMm = 1000.0,
    int numPiles = 4,
    double superstructureSpanM = 35.0,
    double waterDepthAboveBedM = 4.0,
  }) async {
    final body = {
      'peak_discharge_Q': peakDischargeQ,
      'lacey_silt_factor_f': laceySiltFactorF,
      'pile_diameter_mm': pileDiameterMm,
      'num_piles': numPiles,
      'superstructure_span_m': superstructureSpanM,
      'water_depth_above_bed_m': waterDepthAboveBedM,
    };

    final response = await _httpClient.post(
      Uri.parse('$baseUrl/estimate/bridge-pile'),
      headers: _headers,
      body: jsonEncode(body),
    );
    return jsonDecode(response.body);
  }

  /// Swimming Pool & Liquid Retaining Structures (IS 3370)
  Future<Map<String, dynamic>> estimateSwimmingPool({
    required double lengthM,
    required double widthM,
    required double depthM,
    double wallThicknessM = 0.25,
    double raftThicknessM = 0.30,
  }) async {
    final body = {
      'length_m': lengthM,
      'width_m': widthM,
      'depth_m': depthM,
      'wall_thickness_m': wallThicknessM,
      'raft_thickness_m': raftThicknessM,
    };

    final response = await _httpClient.post(
      Uri.parse('$baseUrl/estimate/swimming-pool'),
      headers: _headers,
      body: jsonEncode(body),
    );
    return jsonDecode(response.body);
  }

  /// Fetch Schedule of Rates (SOR 2025-2026)
  Future<Map<String, dynamic>> getRates({String category = 'all'}) async {
    final response = await _httpClient.get(
      Uri.parse('$baseUrl/rates?category=$category'),
      headers: _headers,
    );
    return jsonDecode(response.body);
  }
}

