package com.example.englishvocab.entity;

import jakarta.persistence.*;
import lombok.Getter;
import lombok.Setter;

@Getter
@Setter
@Entity
@Table(name = "Part_of_Speech")
public class PartOfSpeech {
    @Id
    @GeneratedValue(strategy = GenerationType.IDENTITY)
    private Integer partOfSpeechID;

    @Column(nullable = false, unique = true, length = 50)
    private String partOfSpeechName;

    @Column(name = "pos_code", nullable = false, unique = true, length = 10)
    private String posCode;

    private String description;
}
